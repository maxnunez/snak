<?php

declare(strict_types=1);

namespace Tests\Database;

use App\Services\LoginThrottleService;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class LoginThrottleServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $namespace   = null;
    protected $DBGroup     = 'tests';

    private LoginThrottleService $throttle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->table('login_attempts')->truncate();
        $this->db->table('users')->truncate();

        Time::setTestNow('2026-10-21 10:00:00');
        $this->throttle = new LoginThrottleService($this->db);
    }

    protected function tearDown(): void
    {
        Time::setTestNow();

        parent::tearDown();
    }

    public function testCountsFailuresInWindowAndAtBoundary(): void
    {
        $this->insertFailure('2026-10-21 09:45:00');

        $this->assertSame(1, $this->throttle->failures('cashier1', '127.0.0.1'));

        $this->insertFailure('2026-10-21 09:44:59');

        $this->assertSame(1, $this->throttle->failures('cashier1', '127.0.0.1'));
        $this->assertSame(0, $this->throttle->failures('cashier1', '127.0.0.2'));
    }

    public function testIsBlockedAtFiveFailures(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->insertFailure('2026-10-21 09:55:00');
        }

        $this->assertFalse($this->throttle->isBlocked('cashier1', '127.0.0.1'));

        $this->insertFailure('2026-10-21 09:55:00');

        $this->assertTrue($this->throttle->isBlocked('cashier1', '127.0.0.1'));
    }

    public function testPurgeRemovesOlderThan24hButKeepsWindow(): void
    {
        $this->insertFailure('2026-10-20 09:59:59');
        $this->insertFailure('2026-10-21 09:45:00');

        $this->assertSame(1, $this->throttle->purge());
        $this->assertSame(1, $this->db->table('login_attempts')->countAllResults());
    }

    public function testResetWindowLeavesOlderRows(): void
    {
        $this->insertFailure('2026-10-21 09:55:00');
        $this->insertFailure('2026-10-21 09:55:00');
        $this->insertFailure('2026-10-21 09:56:00');
        $this->insertFailure('2026-10-21 08:00:00');

        $this->throttle->resetWindow('cashier1', '127.0.0.1');

        $this->assertSame(1, $this->db->table('login_attempts')->countAllResults());
        $this->assertSame(0, $this->throttle->failures('cashier1', '127.0.0.1'));
    }

    public function testRecordFailurePurgesOldRowsBeforeInsert(): void
    {
        $this->insertFailure('2026-10-20 09:59:59');

        $this->throttle->recordFailure('cashier1', '127.0.0.1');

        $this->assertSame(1, $this->db->table('login_attempts')->countAllResults());

        $row = $this->db->table('login_attempts')->get()->getRow();
        $this->assertSame('cashier1', $row->username);
        $this->assertSame('127.0.0.1', $row->ip_address);
        $this->assertSame('2026-10-21 10:00:00', $row->attempted_at);
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
