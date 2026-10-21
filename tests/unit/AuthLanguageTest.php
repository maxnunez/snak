<?php

declare(strict_types=1);

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class AuthLanguageTest extends CIUnitTestCase
{
    public function testEveryAuthKeyReferencedByIdentifierResolves(): void
    {
        $paths = [
            APPPATH . 'Commands/UserCreate.php',
            APPPATH . 'Controllers/AuthController.php',
            APPPATH . 'Filters/StaffFilter.php',
            APPPATH . 'Services/AuthService.php',
            APPPATH . 'Views/auth/',
            APPPATH . 'Views/cashier/',
            APPPATH . 'Views/errors/403.php',
        ];

        $sources = '';

        foreach ($paths as $path) {
            if (is_dir($path)) {
                foreach (glob($path . '*.php') ?: [] as $file) {
                    $sources .= file_get_contents($file);
                }

                continue;
            }

            if (is_file($path)) {
                $sources .= file_get_contents($path);
            }
        }

        preg_match_all("/lang\\('Auth\\.([A-Za-z0-9_]+)'\\)/", $sources, $matches);

        $referenced = array_values(array_unique($matches[1]));
        $this->assertNotSame([], $referenced, 'No Auth.* references found.');

        $language = require APPPATH . 'Language/es/Auth.php';

        foreach ($referenced as $key) {
            $this->assertArrayHasKey($key, $language, "Missing Auth.{$key}");
            $this->assertIsString($language[$key]);
            $this->assertNotSame('', trim($language[$key]), "Empty Auth.{$key}");
        }
    }
}
