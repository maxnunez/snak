<?php

declare(strict_types=1);

use CodeIgniter\Config\DotEnv;

/*
 * Load the test-only environment first so that `.env.testing` wins over `.env`.
 * DotEnv never overwrites a variable that already has a non-empty value, and
 * the framework bootstrap loads `.env` afterwards.
 */
require_once __DIR__ . '/../vendor/codeigniter4/framework/system/Config/DotEnv.php';

(new DotEnv(dirname(__DIR__), '.env.testing'))->load();

require __DIR__ . '/../vendor/codeigniter4/framework/system/Test/bootstrap.php';
