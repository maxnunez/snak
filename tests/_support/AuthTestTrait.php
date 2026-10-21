<?php

declare(strict_types=1);

namespace Tests\Support;

use CodeIgniter\Config\Services;
use CodeIgniter\Session\Handlers\ArrayHandler;
use CodeIgniter\Test\TestResponse;
use Config\Session;

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

    /**
     * Replaces the framework MockSession with a double whose destroy() is not
     * a silent no-op, so a second destroy fails loudly like production.
     */
    protected function injectDestroyCountingSession(): DestroyCountingSession
    {
        $config  = config(Session::class);
        $session = new DestroyCountingSession(new ArrayHandler($config, '0.0.0.0'), $config);

        Services::injectMock('session', $session);

        return $session;
    }
}
