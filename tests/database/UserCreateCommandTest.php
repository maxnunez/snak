<?php

declare(strict_types=1);

namespace Tests\Database;

use App\Commands\UserCreate;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\StreamFilterTrait;

/**
 * @internal
 */
final class UserCreateCommandTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use StreamFilterTrait;

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

    public function testCreatesActiveCashierViaAskSeam(): void
    {
        $exit = $this->runCommand('newcashier', 'Sup3rSecretPass!', 'cashier');

        $this->assertSame(EXIT_SUCCESS, $exit);

        $row = $this->db->table('users')->where('username', 'newcashier')->get()->getRow();

        $this->assertSame('cashier', $row->role);
        $this->assertSame(1, (int) $row->is_active);
        $this->assertNull($row->last_login_at);
        $this->assertTrue(password_verify('Sup3rSecretPass!', $row->password_hash));
        $this->assertStringContainsString("User #{$row->id} created with role cashier.", $this->getStreamFilterBuffer());
    }

    public function testCreatesAdminWhenRoleSelected(): void
    {
        $exit = $this->runCommand('boss', 'Password123!', 'admin');

        $this->assertSame(EXIT_SUCCESS, $exit);

        $row = $this->db->table('users')->where('username', 'boss')->get()->getRow();
        $this->assertSame('admin', $row->role);
    }

    public function testDuplicateUsernameExitsNonZeroAndWritesNothing(): void
    {
        service('auth')->createUser('existing', 'Password123!');
        $before = $this->db->table('users')->countAllResults();

        $exit = $this->runCommand('existing', 'AnotherPass123!');

        $this->assertSame(EXIT_ERROR, $exit);
        $this->assertStringContainsString(lang('Auth.usernameDuplicate'), $this->getStreamFilterBuffer());
        $this->assertSame($before, $this->db->table('users')->countAllResults());
    }

    public function testInvalidUsernameRejectedWithSpanishMessage(): void
    {
        $exit = $this->runCommand('bad name!', 'Password123!');

        $this->assertSame(EXIT_ERROR, $exit);
        $this->assertStringContainsString(lang('Auth.usernameInvalid'), $this->getStreamFilterBuffer());
        $this->assertSame(0, $this->db->table('users')->countAllResults());
    }

    public function testSevenBytePasswordRejectedWithSpanishMessage(): void
    {
        $exit = $this->runCommand('shortpass', 'short12');

        $this->assertSame(EXIT_ERROR, $exit);
        $this->assertStringContainsString(lang('Auth.passwordLength'), $this->getStreamFilterBuffer());
        $this->assertSame(0, $this->db->table('users')->countAllResults());
    }

    public function testCapturedOutputHasNoPasswordOrHash(): void
    {
        $exit = $this->runCommand('quietuser', 'Zx9!VerySecret42');

        $this->assertSame(EXIT_SUCCESS, $exit);

        $row    = $this->db->table('users')->where('username', 'quietuser')->get()->getRow();
        $output = $this->getStreamFilterBuffer();

        $this->assertStringNotContainsString('Zx9!VerySecret42', $output);
        $this->assertStringNotContainsString($row->password_hash, $output);
    }

    private function runCommand(string $username, string $password, string $role = 'cashier'): int
    {
        $command            = new UserCreateTestDouble(service('logger'), service('commands'));
        $command->username  = $username;
        $command->password  = $password;
        $command->role      = $role;

        return $command->run([]);
    }
}

/**
 * Test seam: bypasses the interactive password prompt without touching stdin.
 */
final class UserCreateTestDouble extends UserCreate
{
    public string $username = '';
    public string $password = '';
    public string $role     = 'cashier';

    protected function askUsername(): string
    {
        return $this->username;
    }

    protected function askPassword(): string
    {
        return $this->password;
    }

    protected function askRole(): string
    {
        return $this->role;
    }
}
