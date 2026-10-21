<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\AuthService;
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

    public function testDeactivatedMidSessionDestroysSessionAndShowsExpiredNoticeOnLogin(): void
    {
        $id      = service('auth')->createUser('cashier1', 'Password123!');
        $spy     = $this->injectTeardownSession();

        $this->withSession(['user_id' => $id]);
        model('App\Models\UserModel')->setActive($id, false);

        $result = $this->get('cashier');

        $result->assertRedirectTo('/login');
        $this->assertNull(session('user_id'));
        $this->assertSame(1, $spy->destroyCalls, 'The session must be destroyed exactly once.');

        $notice = $result->response()->getCookie(AuthService::NOTICE_COOKIE);

        $this->assertNotNull($notice, 'The redirect must carry the notice outside the destroyed session.');
        $this->assertSame(AuthService::NOTICE_SESSION_EXPIRED, $notice->getValue());

        // Follow the redirect as the browser does: notice cookie, empty session.
        service('superglobals')->setCookie(AuthService::NOTICE_COOKIE, $notice->getValue());
        $this->withSession([]);

        $login = $this->get('login');

        $login->assertStatus(200);
        $this->assertStringContainsString(
            lang('Auth.sessionExpired'),
            (string) $login->response()->getBody(),
            'The login page must render the Spanish session-expired message.',
        );

        // One-time: the first login-page render consumes the notice.
        service('superglobals')->setCookie(AuthService::NOTICE_COOKIE, '');

        $again = $this->get('login');

        $again->assertStatus(200);
        $this->assertStringNotContainsString(
            lang('Auth.sessionExpired'),
            (string) $again->response()->getBody(),
        );

        service('superglobals')->unsetCookie(AuthService::NOTICE_COOKIE);
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
