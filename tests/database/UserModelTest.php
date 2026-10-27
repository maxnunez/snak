<?php

declare(strict_types=1);

namespace Tests\Database;

use App\Exceptions\ValidationException;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class UserModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $namespace   = null;
    protected $DBGroup     = 'tests';

    protected function setUp(): void
    {
        parent::setUp();

        // Disable FK checks, truncate, re-enable
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
        $this->db->table('sale_items')->truncate();
        $this->db->table('sales')->truncate();
        $this->db->table('login_attempts')->truncate();
        $this->db->table('users')->truncate();
        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
    }

    protected function tearDown(): void
    {
        Time::setTestNow();

        parent::tearDown();
    }

    public function testCreateStaffStoresHashRoleAndNullLastLogin(): void
    {
        $id = service('auth')->createUser('cashier1', 'Password123!', 'cashier');

        $this->assertGreaterThan(0, $id);

        $row = $this->db->table('users')->where('id', $id)->get()->getRow();

        $this->assertSame('cashier1', $row->username);
        $this->assertSame('cashier', $row->role);
        $this->assertSame(1, (int) $row->is_active);
        $this->assertNull($row->last_login_at);
        $this->assertNotSame('Password123!', $row->password_hash);
        $this->assertTrue(password_verify('Password123!', $row->password_hash));
    }

    public function testDuplicateUsernameRejectedByValidationAndIndex(): void
    {
        service('auth')->createUser('cashier1', 'Password123!');

        $caught = null;

        try {
            service('auth')->createUser('cashier1', 'Password123!');
        } catch (ValidationException $exception) {
            $caught = $exception;
        }

        $this->assertInstanceOf(ValidationException::class, $caught);
        $this->assertArrayHasKey('username', $caught->getErrors());
        $this->assertSame(1, $this->db->table('users')->countAllResults());

        $this->expectException(DatabaseException::class);
        $this->db->table('users')->insert([
            'username'      => 'cashier1',
            'password_hash' => 'hash',
        ]);
    }

    public function testRoleOutsideEnumRejected(): void
    {
        $caught = null;

        try {
            service('auth')->createUser('badrole', 'Password123!', 'manager');
        } catch (ValidationException $exception) {
            $caught = $exception;
        }

        $this->assertInstanceOf(ValidationException::class, $caught);
        $this->assertArrayHasKey('role', $caught->getErrors());
        $this->assertSame(0, $this->db->table('users')->countAllResults());

        $this->expectException(DatabaseException::class);
        $this->db->table('users')->insert([
            'username'      => 'rawrole',
            'password_hash' => 'hash',
            'role'          => 'manager',
        ]);
    }

    public function testRoleAndIsActiveNotMassAssignable(): void
    {
        $model = model('App\Models\UserModel');

        $id = $model->insert([
            'username'      => 'massassign',
            'password_hash' => 'hash',
            'role'          => 'admin',
            'is_active'     => 0,
        ]);

        $this->assertNotFalse($id);

        $row = $this->db->table('users')->where('id', $id)->get()->getRow();
        $this->assertSame('cashier', $row->role);
        $this->assertSame(1, (int) $row->is_active);

        $this->assertTrue($model->setActive((int) $id, false));
        $row = $this->db->table('users')->where('id', $id)->get()->getRow();
        $this->assertSame(0, (int) $row->is_active);
    }

    public function testPasswordRoundTripAndSevenByteRejection(): void
    {
        $service = service('auth');
        $id      = $service->createUser('roundtrip', 'Password123!');

        $hash = $this->db->table('users')->where('id', $id)->get()->getRow()->password_hash;

        $this->assertNotSame('Password123!', $hash);
        $this->assertTrue(password_verify('Password123!', $hash));
        $this->assertFalse(password_verify('password123!', $hash));

        $caught = null;

        try {
            $service->createUser('tooshort', 'short12');
        } catch (ValidationException $exception) {
            $caught = $exception;
        }

        $this->assertInstanceOf(ValidationException::class, $caught);
        $this->assertArrayHasKey('password', $caught->getErrors());
        $this->assertSame(1, $this->db->table('users')->countAllResults());

        $caught = null;

        try {
            $service->createUser('toolong', str_repeat('a', 73));
        } catch (ValidationException $exception) {
            $caught = $exception;
        }

        $this->assertInstanceOf(ValidationException::class, $caught);
        $this->assertArrayHasKey('password', $caught->getErrors());
        $this->assertSame(1, $this->db->table('users')->countAllResults());
    }
}
