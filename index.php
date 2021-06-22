<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

require_once './errorHandler.php';
require_once './exceptionHandler.php'; // just in case

use RC\Infrastructure\Dotenv\EnvironmentDependentEnvFile;
use RC\Infrastructure\ExecutionEnvironmentAdapter\YandexServerless;

(new EnvironmentDependentEnvFile())->load();

function handler(array $event, $context) {
    return
        (new YandexServerless(
            new UserStoryFromRequest(
                new FromYandexServerlessEnvironmentRequest($event)
            )
        ))
            ->response();
}
