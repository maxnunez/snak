<?php

declare(strict_types=1);

namespace Tests\Support;

use CodeIgniter\Test\Mock\MockSession;
use RuntimeException;

/**
 * Session double that keeps MockSession's no-op behaviour for everything
 * except destroy(): it counts calls and fails on the second one, which is
 * exactly the production failure ("Trying to destroy uninitialized session").
 *
 * A plain MockSession hides the double-destroy bug because destroy() is a
 * no-op under ENVIRONMENT === 'testing'.
 */
final class DestroyCountingSession extends MockSession
{
    public int $destroyCalls = 0;

    public function destroy()
    {
        $this->destroyCalls++;

        if ($this->destroyCalls > 1) {
            throw new RuntimeException('session_destroy(): Trying to destroy uninitialized session');
        }
    }
}
