<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;

/**
 * Database-aware health probe.
 *
 * Body stays a stable English contract: {"status":"ok|error","db":"ok|error"}.
 * Failure details go to writable/logs only.
 */
class Health extends Controller
{
    public function index(): ResponseInterface
    {
        $dbOk = true;

        try {
            Database::connect()->query('SELECT 1');
        } catch (\Throwable $e) {
            $dbOk = false;
            log_message('error', 'Health check database failure: {message}', ['message' => $e->getMessage()]);
        }

        $status = $dbOk ? 'ok' : 'error';

        return $this->response
            ->setStatusCode($dbOk ? 200 : 503)
            ->setHeader('Cache-Control', 'no-store')
            ->setJSON(['status' => $status, 'db' => $status]);
    }
}
