<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Models\UserModel;
use CodeIgniter\I18n\Time;

/**
 * Single owner of credential rules, session identity and staff creation.
 *
 * No session lookups are memoised: a role or active-flag change must take
 * effect on the very next request.
 */
class AuthService
{
    public const SESSION_USER_ID  = 'user_id';
    public const SESSION_INTENDED = 'intended_url';

    /**
     * One-time notice carried in a cookie because a flash written to the
     * session after logout() has already been destroyed is never delivered.
     */
    public const NOTICE_COOKIE          = 'snak_auth_notice';
    public const NOTICE_SESSION_EXPIRED = 'session_expired';

    private const SESSION_TERMINATED = 'auth_terminated';

    private const PASSWORD_MIN_BYTES = 8;
    private const PASSWORD_MAX_BYTES = 72;

    public function __construct(
        private readonly UserModel $users,
        private readonly LoginThrottleService $throttle,
    ) {
    }

    public function createUser(
        string $username,
        string $password,
        string $role = 'cashier',
        bool $active = true,
    ): int {
        $validation = service('validation', null, false);
        $validation->setRules([
            'username' => [
                'rules'  => 'required|min_length[3]|max_length[50]|regex_match[/^[a-z0-9._-]+$/]|is_unique[users.username]',
                'errors' => [
                    'required'   => 'Auth.usernameRequired',
                    'min_length' => 'Auth.usernameInvalid',
                    'max_length' => 'Auth.usernameInvalid',
                    'regex_match' => 'Auth.usernameInvalid',
                    'is_unique'  => 'Auth.usernameDuplicate',
                ],
            ],
            'role' => [
                'rules'  => 'required|in_list[cashier,admin]',
                'errors' => [
                    'required' => 'Auth.roleInvalid',
                    'in_list'  => 'Auth.roleInvalid',
                ],
            ],
        ]);

        if (! $validation->run(['username' => $username, 'role' => $role])) {
            throw new ValidationException($validation->getErrors());
        }

        $length = strlen($password);

        if ($length < self::PASSWORD_MIN_BYTES || $length > self::PASSWORD_MAX_BYTES) {
            throw new ValidationException(['password' => lang('Auth.passwordLength')]);
        }

        return $this->users->createStaff($username, $this->hashPassword($password), $role, $active);
    }

    /**
     * Verifies credentials and only then touches the session.
     *
     * Unknown, wrong-password and inactive accounts all return false so the
     * caller can show one generic message (no user enumeration).
     */
    public function attempt(string $username, string $password, string $ip): bool
    {
        $username = self::normalize($username);

        if ($this->throttle->isBlocked($username, $ip)) {
            $this->verifyPassword($password, self::dummyHash());

            return false;
        }

        $user = $this->users->findByUsername($username);

        if ($user === null) {
            $this->verifyPassword($password, self::dummyHash());
            $this->throttle->recordFailure($username, $ip);

            return false;
        }

        if (! $this->verifyPassword($password, $user->password_hash)) {
            $this->throttle->recordFailure($username, $ip);

            return false;
        }

        if ((int) $user->is_active !== 1) {
            $this->throttle->recordFailure($username, $ip);

            return false;
        }

        $this->throttle->resetWindow($username, $ip);

        $session = session();
        $session->regenerate(true);
        $session->remove(self::SESSION_TERMINATED);
        $session->set(self::SESSION_USER_ID, (int) $user->id);

        $this->users->update($user->id, [
            'last_login_at' => Time::now()->toDateTimeString(),
        ]);

        return true;
    }

    /**
     * Single owner of session termination. Idempotent by design: callers such
     * as the filter may reach this after currentUser() already logged out,
     * and a second session_destroy() raises "uninitialized session" in PHP.
     */
    public function logout(): void
    {
        $session = session();

        if ($session->get(self::SESSION_TERMINATED) === true) {
            return;
        }

        $session->remove([self::SESSION_USER_ID, self::SESSION_INTENDED]);
        $session->set(self::SESSION_TERMINATED, true);
        $session->destroy();
    }

    /**
     * Always re-reads the row: role and active changes must apply on the
     * next request, and the session stores no role at all.
     */
    public function currentUser(): ?object
    {
        $id = session()->get(self::SESSION_USER_ID);

        if ($id === null) {
            return null;
        }

        $user = $this->users->find((int) $id);

        if ($user === null || (int) $user->is_active !== 1) {
            $this->logout();

            return null;
        }

        return $user;
    }

    public function isAuthenticated(): bool
    {
        return $this->currentUser() !== null;
    }

    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Seam so tests can prove the unknown-user branch still pays a dummy
     * verification instead of returning early.
     */
    protected function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    protected static function normalize(string $username): string
    {
        return strtolower(trim($username));
    }

    /**
     * Built lazily so every unknown-user attempt pays one bcrypt verify,
     * matching the timing of a real wrong-password attempt.
     */
    private static function dummyHash(): string
    {
        static $hash;

        if ($hash === null) {
            $hash = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
        }

        return $hash;
    }
}
