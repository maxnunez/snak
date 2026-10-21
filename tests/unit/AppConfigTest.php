<?php

namespace Tests\Unit;

use App\Filters\StaffFilter;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Cookie;

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

    public function testProductionCookieEnvironmentEnablesSecureHttpOnlyLax(): void
    {
        $hadEnv    = array_key_exists('cookie.secure', $_ENV);
        $envValue  = $_ENV['cookie.secure'] ?? null;
        $hadServer = array_key_exists('cookie.secure', $_SERVER);
        $serverVal = $_SERVER['cookie.secure'] ?? null;

        unset($_ENV['cookie.secure'], $_SERVER['cookie.secure']);

        try {
            $development = new Cookie();
            $this->assertFalse($development->secure, 'Local HTTP development may omit Secure.');
            $this->assertTrue($development->httponly);
            $this->assertSame('Lax', $development->samesite);

            $_ENV['cookie.secure'] = 'true';

            $production = new Cookie();
            $this->assertTrue($production->secure, 'Production must set Secure.');
            $this->assertTrue($production->httponly);
            $this->assertSame('Lax', $production->samesite);
        } finally {
            if ($hadEnv) {
                $_ENV['cookie.secure'] = $envValue;
            } else {
                unset($_ENV['cookie.secure']);
            }

            if ($hadServer) {
                $_SERVER['cookie.secure'] = $serverVal;
            } else {
                unset($_SERVER['cookie.secure']);
            }
        }
    }

    public function testStaffFilterIsRegisteredAndAppliedToCashierRoutes(): void
    {
        $filters = config('Filters');

        $this->assertSame(StaffFilter::class, $filters->aliases['staff']);
        $this->assertContains('csrf', $filters->globals['before']);

        $options = service('routes')->loadRoutes()->getRoutesOptions('cashier', 'GET');

        $this->assertSame(['staff:cashier'], $options['filter'] ?? null);
    }
}
