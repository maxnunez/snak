<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Models\UserModel;

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

    private const PASSWORD_MIN_BYTES = 8;
    private const PASSWORD_MAX_BYTES = 72;

    public function __construct(private readonly UserModel $users)
    {
    }

    public function createUser(
        string $username,
        string $password,
        string $role = 'cashier',
        bool $active = true,
    ): int {
        $validation = service('validation', null, false);
        $validation->setRules([
            'username' => 'required|min_length[3]|max_length[50]|regex_match[/^[a-z0-9._-]+$/]|is_unique[users.username]',
            'role'     => 'required|in_list[cashier,admin]',
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

    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    protected static function normalize(string $username): string
    {
        return strtolower(trim($username));
    }
}
