<?php

declare(strict_types=1);

namespace Tests\Support;

use CodeIgniter\Test\TestResponse;

/**
 * CI4 4.7 feature-test helpers: session snapshots survive between simulated
 * requests, and the CSRF token must be read immediately before each POST
 * because a verified POST rotates the hash.
 */
trait AuthTestTrait
{
    protected function continueSession(): void
    {
        $this->withSession($_SESSION);
    }

    protected function postWithCsrf(string $path, array $params = []): TestResponse
    {
        $this->continueSession();
        $params[config('Security')->tokenName] = csrf_hash();

        return $this->post($path, $params);
    }
}
