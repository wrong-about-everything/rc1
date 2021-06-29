<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RC\Domain\YandexServerless\FallbackResponseBody;
use RC\Infrastructure\Dotenv\EnvironmentDependentEnvFile;
use RC\Infrastructure\ExecutionEnvironmentAdapter\GoogleServerless;
use RC\Infrastructure\Http\Request\Inbound\FromPsrHttpRequest;
use RC\Infrastructure\Http\Request\Inbound\WithPathTakenFromQueryParam;
use RC\Infrastructure\Http\Request\Method\Get;
use RC\Infrastructure\Http\Request\Url\Query;
use RC\Infrastructure\Logging\LogId;
use RC\Infrastructure\Logging\Logs\StdOut;
use RC\Infrastructure\Routing\Route\RouteByMethodAndPathPattern;
use RC\Infrastructure\UserStory\ByRoute;
use RC\Infrastructure\Uuid\RandomUUID;
use RC\UserStories\Sample;

(new EnvironmentDependentEnvFile())->load();

function entryPoint(ServerRequestInterface $request): ResponseInterface {
    return
        (new GoogleServerless(
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
                new WithPathTakenFromQueryParam(
                    'ad_hoc_path',
                    new FromPsrHttpRequest($request)
                )
            ),
            new FallbackResponseBody(),
            new StdOut(new LogId(new RandomUUID()))
        ))
            ->response();
}

var_dump(
    entryPoint(
        ServerRequest::fromGlobals()
    )
        ->getBody()->getContents()
);
