<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

require_once './errorHandler.php';
require_once './exceptionHandler.php'; // just in case

use RC\Infrastructure\Dotenv\EnvironmentDependentEnvFile;
use RC\Infrastructure\ExecutionEnvironmentAdapter\YandexServerless;
use RC\Infrastructure\Http\Request\Method\Get;
use RC\Infrastructure\Http\Request\Url\Query;
use RC\Infrastructure\Routing\Route\RouteByMethodAndPathPattern;
use RC\Infrastructure\UserStory\ByRoute;
use RC\Tests\Infrastructure\UserStories\Sample;

(new EnvironmentDependentEnvFile())->load();

function handler(array $event, $context) {
    return
        (new YandexServerless(
            new ByRoute(
                [
                    [
                        new RouteByMethodAndPathPattern(
                            new Get(),
                            '/hello/:id/world/:name'
                        ),
                        function (string $id, string $name, Query $query) {
                            return new Sample($id, $name, $query);
                        }
                    ],
                ],
                new FromYandexServerlessEnvironmentRequest($event)
            )
        ))
            ->response();
}
