<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class ShellTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testHomeRendersBaseShellWithLocalAssets(): void
    {
        $result = $this->get('/');

        $result->assertStatus(200);
        $body = $result->getBody();

        $this->assertStringContainsString('href="/assets/css/app.css"', $body);
        $this->assertStringContainsString('sweetalert2.all.min.js', $body);
        $this->assertStringContainsString('jquery.dataTables.min.js', $body);
        $this->assertStringContainsString('content="#003366"', $body);
        $this->assertDoesNotMatchRegularExpression('#(?:href|src)=["\']https?://#i', $body);
    }

    public function testShellCopyComesFromSpanishLanguageFile(): void
    {
        $body = $this->get('/')->getBody();

        $this->assertStringContainsString(esc(lang('App.homeTitle')), $body);
        $this->assertStringContainsString(esc(lang('App.welcome')), $body);
    }
}
