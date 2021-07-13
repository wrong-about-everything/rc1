<?php

declare(strict_types=1);

namespace Tanuki\Delivery\Tests;

use Dotenv\Dotenv;
use Exception;

set_error_handler(
    function ($errno, $errstr, $errfile, $errline, array $errcontex) {
        throw new Exception($errstr, 0);
    },
    E_ALL
);

(Dotenv::createUnsafeImmutable(dirname(__DIR__), '.env.dev.testing_mode'))->load();
