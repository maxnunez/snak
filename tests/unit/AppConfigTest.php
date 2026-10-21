<?php

namespace Tests\Unit;

use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class AppConfigTest extends CIUnitTestCase
{
    public function testAppTimezoneIsLaPaz(): void
    {
        $this->assertSame('America/La_Paz', config('App')->appTimezone);
    }

    public function testTimeUsesLaPazAsDefaultTimezone(): void
    {
        $this->assertSame('America/La_Paz', Time::now()->getTimezone()->getName());
    }

    public function testTestsDatabaseGroupPointsAtIsolatedMySqlDatabase(): void
    {
        $tests = config('Database')->tests;

        $this->assertSame('MySQLi', $tests['DBDriver']);
        $this->assertSame('127.0.0.1', $tests['hostname']);
        $this->assertSame('snak_test', $tests['database']);
        $this->assertSame('', $tests['DBPrefix']);
        $this->assertSame('utf8mb4', $tests['charset']);
        $this->assertFalse($tests['numberNative']);
    }
}
