<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RC\Domain\UserStory\Authorized;
use RC\Domain\UserStory\Body\FallbackResponseBody;
use RC\Infrastructure\Dotenv\EnvironmentDependentEnvFile;
use RC\Infrastructure\ExecutionEnvironmentAdapter\GoogleServerless;
use RC\Infrastructure\Filesystem\DirPath\ExistentFromAbsolutePath;
use RC\Infrastructure\Http\Request\Inbound\DefaultInbound;
use RC\Infrastructure\Http\Request\Inbound\FromPsrHttpRequest;
use RC\Infrastructure\Http\Request\Inbound\WithPathTakenFromQueryParam;
use RC\Infrastructure\Http\Request\Method\Get;
use RC\Infrastructure\Http\Request\Url\Query;
use RC\Infrastructure\Logging\LogId;
use RC\Infrastructure\Logging\Logs\StdOut;
use RC\Infrastructure\Routing\Route\RouteByMethodAndPathPattern;
use RC\Infrastructure\Routing\Route\RouteByTelegramBotCommand;
use RC\Infrastructure\TelegramBot\UserCommand\Start;
use RC\Infrastructure\UserStory\ByRoute;
use RC\Infrastructure\Uuid\RandomUUID;
use RC\UserStories\Sample;
use RC\UserStories\User\PressesStart\PressesStart;

(new EnvironmentDependentEnvFile(
    new ExistentFromAbsolutePath(dirname(__FILE__)),
    new DefaultInbound()
))
    ->load();

function entryPoint(ServerRequestInterface $request): ResponseInterface {
    return
        (new GoogleServerless(
            new Authorized(
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
                        [
                            new RouteByTelegramBotCommand(new Start()),
                            function (array $parsedTelegramMessage) {
                                return new PressesStart($parsedTelegramMessage);
                            }
                        ],
//                    [
//                        new RouteByJsonFieldValueInPostBody(
//                            $request->getBody(),
//                            function (array $parsedRequestData) {
//                                return $parsedRequestData[];
//                            }
//                        ),
//                        function (string $id, string $name, Query $query) {
//                            return new Sample($id, $name, $query);
//                        }
//                    ],
                    ],
                    new WithPathTakenFromQueryParam(
                        'ad_hoc_path',
                        new FromPsrHttpRequest($request)
                    )
                ),
                new FromPsrHttpRequest($request)
            ),
            new FallbackResponseBody(),
            new StdOut(new LogId(new RandomUUID()))
        ))
            ->response();
}
