<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\I18n\Time;
use CodeIgniter\Model;

/**
 * Staff accounts.
 *
 * `role` and `is_active` are deliberately excluded from $allowedFields:
 * they are only written through the explicit methods below, so no request
 * payload can mass-assign a privilege.
 */
class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $returnType       = 'object';
    protected $useTimestamps    = true;
    protected $allowedFields    = ['username', 'password_hash', 'last_login_at'];
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $validationRules  = [];
    protected $validationMessages = [];
    protected $skipValidation   = true;

    public function findByUsername(string $username): ?object
    {
        return $this->where('username', $username)->first();
    }

    public function createStaff(
        string $username,
        string $passwordHash,
        string $role = 'cashier',
        bool $active = true,
    ): int {
        $now = Time::now()->toDateTimeString();

        $this->db->table($this->table)->insert([
            'username'      => $username,
            'password_hash' => $passwordHash,
            'role'          => $role,
            'is_active'     => $active ? 1 : 0,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        return (int) $this->db->insertID();
    }

    public function setActive(int $id, bool $active): bool
    {
        return (bool) $this->db->table($this->table)
            ->where('id', $id)
            ->update([
                'is_active'  => $active ? 1 : 0,
                'updated_at' => Time::now()->toDateTimeString(),
            ]);
    }
}
