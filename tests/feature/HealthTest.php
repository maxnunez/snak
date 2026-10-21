<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class HealthTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testHealthReturnsOkWhenDatabaseIsReachable(): void
    {
        $result = $this->get('health');

        $result->assertStatus(200);
        $this->assertSame(
            ['status' => 'ok', 'db' => 'ok'],
            json_decode($result->getJSON(), true),
        );
    }

    public function testHealthResponseIsUncachedJsonWithoutConnectionDetails(): void
    {
        $result = $this->get('health');

        $this->assertStringContainsString('no-store', $result->response()->getHeaderLine('Cache-Control'));
        $result->assertHeader('Content-Type', 'application/json; charset=UTF-8');
        $this->assertSame(
            ['status', 'db'],
            array_keys(json_decode($result->getJSON(), true)),
        );
    }
}
