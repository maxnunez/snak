<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;

/**
 * Per-(username, IP) failed-login counter over a 15-minute window.
 *
 * Retention is 24 hours so the table stays auditable but bounded; purge()
 * runs on every failure write, so no cron is required.
 */
final class LoginThrottleService
{
    public const WINDOW_MINUTES  = 15;
    public const MAX_FAILURES    = 5;
    public const RETENTION_HOURS = 24;

    public function __construct(private readonly BaseConnection $db)
    {
    }

    public function failures(string $username, string $ip): int
    {
        return $this->db->table('login_attempts')
            ->where('username', $username)
            ->where('ip_address', $ip)
            ->where('attempted_at >=', $this->windowStart())
            ->countAllResults();
    }

    public function isBlocked(string $username, string $ip): bool
    {
        return $this->failures($username, $ip) >= self::MAX_FAILURES;
    }

    public function recordFailure(string $username, string $ip): void
    {
        $this->purge();

        $this->db->table('login_attempts')->insert([
            'username'     => $username,
            'ip_address'   => $ip,
            'attempted_at' => Time::now()->toDateTimeString(),
        ]);
    }

    public function resetWindow(string $username, string $ip): void
    {
        $this->db->table('login_attempts')
            ->where('username', $username)
            ->where('ip_address', $ip)
            ->where('attempted_at >=', $this->windowStart())
            ->delete();
    }

    public function purge(): int
    {
        $cutoff = Time::now()->subHours(self::RETENTION_HOURS)->toDateTimeString();

        $this->db->table('login_attempts')
            ->where('attempted_at <', $cutoff)
            ->delete();

        return $this->db->affectedRows();
    }

    private function windowStart(): string
    {
        return Time::now()->subMinutes(self::WINDOW_MINUTES)->toDateTimeString();
    }
}
