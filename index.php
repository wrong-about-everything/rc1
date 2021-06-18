<?php

declare(strict_types=1);

use RC\Infrastructure\Dotenv\EnvironmentDependentEnvFile;

require_once __DIR__ . '/vendor/autoload.php';

require_once './errorHandler.php';
require_once './exceptionHandler.php'; // just in case

(new EnvironmentDependentEnvFile())->load();

function handler(array $event, $context) {
    return [
        'statusCode' => 200,
        'body' => 'Hello, World!',
    ];
}
