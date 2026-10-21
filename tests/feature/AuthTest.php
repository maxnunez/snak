<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\AuthService;
use App\Services\LoginThrottleService;
use CodeIgniter\I18n\Time;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;

/**
 * @internal
 */
final class AuthTest extends CIUnitTestCase
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
        service('superglobals')->setServer('REMOTE_ADDR', '127.0.0.1');
        Time::setTestNow('2026-10-21 10:00:00');
    }

    protected function tearDown(): void
    {
        Time::setTestNow();
        $_SESSION = [];

        parent::tearDown();
    }

    public function testLoginRegeneratesSessionUpdatesLastLoginAndRedirects(): void
    {
        $id = service('auth')->createUser('cashier1', 'Password123!');

        $result = $this->postWithCsrf('login', [
            'username' => 'cashier1',
            'password' => 'Password123!',
        ]);

        $result->assertRedirectTo('/cashier');
        $this->assertTrue(service('session')->didRegenerate);
        $this->assertSame($id, session('user_id'));

        $row = $this->db->table('users')->where('id', $id)->get()->getRow();
        $this->assertSame('2026-10-21 10:00:00', $row->last_login_at);
    }

    public function testWrongPasswordShowsGenericAlertAndNoSession(): void
    {
        service('auth')->createUser('cashier1', 'Password123!');

        $result = $this->postWithCsrf('login', [
            'username' => 'cashier1',
            'password' => 'WrongPass123!',
        ]);

        $result->assertRedirectTo('/login');
        $result->assertSessionHas('authError', lang('Auth.loginFailed'));
        $this->assertNull(session('user_id'));
        $this->assertFalse(service('session')->didRegenerate);
    }

    public function testUnknownUserMatchesWrongPasswordStatusAndMessage(): void
    {
        service('auth')->createUser('cashier1', 'Password123!');

        $wrong = $this->postWithCsrf('login', [
            'username' => 'cashier1',
            'password' => 'WrongPass123!',
        ]);

        $wrong->assertSessionHas('authError', lang('Auth.loginFailed'));

        $this->continueSession();

        $unknown = $this->postWithCsrf('login', [
            'username' => 'ghost',
            'password' => 'WrongPass123!',
        ]);

        $this->assertSame(
            $wrong->response()->getStatusCode(),
            $unknown->response()->getStatusCode(),
        );
        $this->assertSame($wrong->getRedirectUrl(), $unknown->getRedirectUrl());
        $unknown->assertSessionHas('authError', lang('Auth.loginFailed'));
    }

    public function testInactiveUserRefusedWithGenericMessage(): void
    {
        service('auth')->createUser('idle', 'Password123!', 'cashier', false);

        $result = $this->postWithCsrf('login', [
            'username' => 'idle',
            'password' => 'Password123!',
        ]);

        $result->assertRedirectTo('/login');
        $result->assertSessionHas('authError', lang('Auth.loginFailed'));
        $this->assertNull(session('user_id'));
    }

    public function testLogoutDestroysSessionAndRejectsReplay(): void
    {
        $id = service('auth')->createUser('cashier1', 'Password123!');

        $this->postWithCsrf('login', [
            'username' => 'cashier1',
            'password' => 'Password123!',
        ]);

        $this->assertSame($id, session('user_id'));

        $result = $this->postWithCsrf('logout');

        $result->assertRedirectTo('/login');
        $this->assertNull(session('user_id'));
        $this->assertFalse(service('auth')->isAuthenticated());
        $this->assertNull(service('auth')->currentUser());
    }

    public function testLogoutDestroysSessionExactlyOnceEvenIfCalledTwice(): void
    {
        $id  = service('auth')->createUser('cashier1', 'Password123!');
        $spy = $this->injectDestroyCountingSession();

        $_SESSION['user_id'] = $id;

        service('auth')->logout();

        $this->assertSame(1, $spy->destroyCalls);
        $this->assertNull(session('user_id'));

        service('auth')->logout();

        $this->assertSame(1, $spy->destroyCalls, 'Logout must be idempotent.');
    }

    public function testLogoutWithInvalidCsrfTokenKeepsSessionIntact(): void
    {
        $id = service('auth')->createUser('cashier1', 'Password123!');

        $this->withSession(['user_id' => $id]);

        try {
            $this->post('logout', [config('Security')->tokenName => 'not-a-valid-token']);
            $this->fail('Expected SecurityException for an invalid logout token.');
        } catch (SecurityException) {
            // Non-production Security::$redirect is false, so the filter throws.
        }

        $this->assertSame($id, session('user_id'));

        $this->continueSession();
        $this->get('cashier')->assertStatus(200);
    }

    public function testUnknownUserRunsDummyPasswordVerification(): void
    {
        $id  = service('auth')->createUser('cashier1', 'Password123!');
        $row = $this->db->table('users')->where('id', $id)->get()->getRow();

        $unknown = $this->recordingAuth();
        $this->assertFalse($unknown->attempt('ghost', 'WrongPass123!', '127.0.0.1'));
        $this->assertCount(1, $unknown->verifiedHashes);
        $this->assertNotSame($row->password_hash, $unknown->verifiedHashes[0]);

        $known = $this->recordingAuth();
        $this->assertFalse($known->attempt('cashier1', 'WrongPass123!', '127.0.0.1'));
        $this->assertSame([$row->password_hash], $known->verifiedHashes);
    }

    public function testMissingCsrfTokenThrowsOnPostLogin(): void
    {
        $this->expectException(SecurityException::class);

        $this->post('login', [
            'username' => 'cashier1',
            'password' => 'Password123!',
        ]);
    }

    public function testInvalidCsrfTokenThrows(): void
    {
        $this->expectException(SecurityException::class);

        $this->post('login', [
            config('Security')->tokenName => 'not-a-valid-token',
            'username'                   => 'cashier1',
            'password'                   => 'Password123!',
        ]);
    }

    public function testIntendedUrlRestoredAfterLogin(): void
    {
        $id = service('auth')->createUser('cashier1', 'Password123!');
        $_SESSION['intended_url'] = '/cashier/products';

        $result = $this->postWithCsrf('login', [
            'username' => 'cashier1',
            'password' => 'Password123!',
        ]);

        $result->assertRedirectTo('/cashier/products');
        $this->assertSame($id, session('user_id'));
        $this->assertArrayNotHasKey('intended_url', $_SESSION);
    }

    public function testUnsafeIntendedUrlFallsBackToCashier(): void
    {
        service('auth')->createUser('cashier1', 'Password123!');
        $_SESSION['intended_url'] = '//evil.test/cashier';

        $result = $this->postWithCsrf('login', [
            'username' => 'cashier1',
            'password' => 'Password123!',
        ]);

        $result->assertRedirectTo('/cashier');
    }

    public function testEmptyFieldsReturnSpanishInlineErrors(): void
    {
        $result = $this->postWithCsrf('login', [
            'username' => '',
            'password' => '',
        ]);

        $result->assertRedirectTo('/login');
        $result->assertSessionHas('errors');

        $errors = session('errors');
        $this->assertSame(lang('Auth.usernameRequired'), $errors['username'] ?? null);
        $this->assertSame(lang('Auth.passwordRequired'), $errors['password'] ?? null);
    }

    public function testLoginPageRendersSweetAlertAssetsWithoutDataTableCall(): void
    {
        $this->withSession(['authError' => lang('Auth.loginFailed')]);

        $result = $this->get('login');
        $result->assertStatus(200);

        $body = $result->response()->getBody();

        $this->assertStringContainsString('sweetalert2.all.min.js', $body);
        $this->assertStringContainsString('Swal.fire', $body);
        $this->assertStringContainsString(json_encode(lang('Auth.loginFailed')), $body);
        $this->assertStringNotContainsString('DataTable(', $body);
        $this->assertStringContainsString('csrf_test_name', $body);
        $this->assertStringContainsString(lang('Auth.loginTitle'), $body);
    }

    public function testAuthenticatedUserIsRedirectedAwayFromLogin(): void
    {
        $id = service('auth')->createUser('cashier1', 'Password123!');

        $this->withSession(['user_id' => $id]);

        $this->get('login')->assertRedirectTo('/cashier');
    }

    public function testSixthAttemptBlockedEvenWithCorrectPassword(): void
    {
        service('auth')->createUser('cashier1', 'Password123!');

        for ($i = 0; $i < 5; $i++) {
            $this->insertFailure('2026-10-21 09:55:00');
        }

        $result = $this->postWithCsrf('login', [
            'username' => 'cashier1',
            'password' => 'Password123!',
        ]);

        $result->assertRedirectTo('/login');
        $result->assertSessionHas('authError', lang('Auth.loginFailed'));
        $this->assertNull(session('user_id'));
        $this->assertSame(5, $this->db->table('login_attempts')->countAllResults());
    }

    public function testFailuresOlderThanWindowAllowed(): void
    {
        $id = service('auth')->createUser('cashier1', 'Password123!');

        for ($i = 0; $i < 5; $i++) {
            $this->insertFailure('2026-10-21 09:44:59');
        }

        $result = $this->postWithCsrf('login', [
            'username' => 'cashier1',
            'password' => 'Password123!',
        ]);

        $result->assertRedirectTo('/cashier');
        $this->assertSame($id, session('user_id'));
    }

    public function testSuccessfulLoginResetsWindow(): void
    {
        service('auth')->createUser('cashier1', 'Password123!');

        for ($i = 0; $i < 4; $i++) {
            $this->insertFailure('2026-10-21 09:55:00');
        }

        $this->postWithCsrf('login', [
            'username' => 'cashier1',
            'password' => 'Password123!',
        ])->assertRedirectTo('/cashier');

        $this->assertSame(0, $this->db->table('login_attempts')->countAllResults());
    }

    private function recordingAuth(): RecordingAuthService
    {
        return new RecordingAuthService(
            model(\App\Models\UserModel::class),
            new LoginThrottleService(db_connect()),
        );
    }

    private function insertFailure(string $attemptedAt): void
    {
        $this->db->table('login_attempts')->insert([
            'username'     => 'cashier1',
            'ip_address'   => '127.0.0.1',
            'attempted_at' => $attemptedAt,
        ]);
    }
}

/**
 * Records which hashes AuthService::attempt() verifies, so the dummy-verify
 * branch for unknown users is observable without timing heuristics.
 */
final class RecordingAuthService extends AuthService
{
    /** @var list<string> */
    public array $verifiedHashes = [];

    protected function verifyPassword(string $password, string $hash): bool
    {
        $this->verifiedHashes[] = $hash;

        return parent::verifyPassword($password, $hash);
    }
}
