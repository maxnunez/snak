<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;

/**
 * @internal
 */
final class StaffFilterTest extends CIUnitTestCase
{
    use AuthTestTrait;
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $namespace   = null;
    protected $DBGroup     = 'tests';

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->table('login_attempts')->truncate();
        $this->db->table('users')->truncate();

        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];

        parent::tearDown();
    }

    public function testAnonymousRequestRedirectsAndStoresIntendedUrl(): void
    {
        $result = $this->get('cashier');

        $result->assertRedirectTo('/login');
        $this->assertSame('/cashier', $_SESSION['intended_url'] ?? null);
        $this->assertStringNotContainsString(lang('Auth.cashierHome'), (string) $result->response()->getBody());
    }

    public function testCashierGetsCashierHome200(): void
    {
        $id = service('auth')->createUser('cashier1', 'Password123!');

        $this->withSession(['user_id' => $id]);

        $result = $this->get('cashier');

        $result->assertStatus(200);
        $result->assertSee(lang('Auth.cashierHome'));
        $result->assertSee('cashier1');
        $this->assertStringNotContainsString('DataTable(', $result->response()->getBody());
    }

    public function testAdminArgumentRejectsCashierWith403AndSpanishCopy(): void
    {
        $id = service('auth')->createUser('cashier1', 'Password123!');

        $this->withRoutes([
            ['GET', 'cashier/products', static fn () => 'products', ['filter' => 'staff:cashier']],
            ['GET', 'cashier/admin-only', static fn () => 'admin', ['filter' => 'staff:admin']],
        ]);
        $this->withSession(['user_id' => $id]);

        $result = $this->get('cashier/admin-only');

        $result->assertStatus(403);
        $this->assertStringContainsString(lang('Auth.forbidden'), $result->response()->getBody());
        $this->assertStringNotContainsString('cashier1', $result->response()->getBody());

        $this->continueSession();

        $this->get('cashier/products')->assertStatus(200);
    }

    public function testDeactivatedMidSessionDestroysSessionExactlyOnceAndRedirects(): void
    {
        $id      = service('auth')->createUser('cashier1', 'Password123!');
        $spy     = $this->injectDestroyCountingSession();

        $this->withSession(['user_id' => $id]);
        model('App\Models\UserModel')->setActive($id, false);

        $result = $this->get('cashier');

        $result->assertRedirectTo('/login');
        $result->assertSessionHas('authError', lang('Auth.sessionExpired'));
        $this->assertNull(session('user_id'));
        $this->assertSame(1, $spy->destroyCalls, 'The session must be destroyed exactly once.');
    }

    public function testPublicRoutesRemainPublic(): void
    {
        $this->get('/')->assertStatus(200);
        $this->get('login')->assertStatus(200);
        $this->get('health')->assertStatus(200);
    }

    public function testRoleChangeTakesEffectOnNextRequest(): void
    {
        $id = service('auth')->createUser('cashier1', 'Password123!');

        $this->withSession(['user_id' => $id]);
        $this->db->table('users')->where('id', $id)->update(['role' => 'admin']);

        $this->get('cashier')->assertStatus(403);
    }
}
