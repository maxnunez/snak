<?php

declare(strict_types=1);

namespace Tests\Support;

use CodeIgniter\Test\Mock\MockSession;

/**
 * Session double that models what the next request sees after a real
 * session_destroy(): the persisted data is gone and any later write is lost.
 *
 * MockSession's destroy() is a no-op under ENVIRONMENT === 'testing', which
 * makes a post-destroy flash look deliverable when production drops it.
 */
final class TeardownSession extends MockSession
{
    public int $destroyCalls = 0;

    private bool $tornDown = false;

    public function destroy()
    {
        $this->destroyCalls++;
        $this->tornDown = true;

        $_SESSION = [];
    }

    public function set($data, $value = null)
    {
        if ($this->tornDown) {
            return;
        }

        parent::set($data, $value);
    }

    public function setFlashdata($data, $value = null)
    {
        if ($this->tornDown) {
            return;
        }

        parent::setFlashdata($data, $value);
    }
}
