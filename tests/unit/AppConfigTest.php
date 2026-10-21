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
}
