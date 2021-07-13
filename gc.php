<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RC\Domain\UserStory\Authorized;
use RC\Domain\UserStory\Body\FallbackResponseBody;
use RC\Infrastructure\Dotenv\EnvironmentDependentEnvFile;
use RC\Infrastructure\ExecutionEnvironmentAdapter\GoogleServerless;
use RC\Infrastructure\Filesystem\DirPath\ExistentFromAbsolutePathString as ExistentDirPathFromAbsolutePathString;
use RC\Infrastructure\Filesystem\DirPath\FromNestedDirectoryNames;
use RC\Infrastructure\Filesystem\Filename\PortableFromString;
use RC\Infrastructure\Filesystem\FilePath\ExistentFromAbsolutePathString as ExistentFilePathFromAbsolutePathString;
use RC\Infrastructure\Filesystem\FilePath\ExistentFromDirAndFileName;
use RC\Infrastructure\Http\Request\Inbound\DefaultInbound;
use RC\Infrastructure\Http\Request\Inbound\FromPsrHttpRequest;
use RC\Infrastructure\Http\Request\Inbound\WithPathTakenFromQueryParam;
use RC\Infrastructure\Http\Request\Method\Get;
use RC\Infrastructure\Http\Request\Url\Query;
use RC\Infrastructure\Http\Transport\EnvironmentDependentTransport;
use RC\Infrastructure\Http\Transport\Guzzle\DefaultGuzzle;
use RC\Infrastructure\Logging\LogId;
use RC\Infrastructure\Logging\Logs\EnvironmentDependentLogs;
use RC\Infrastructure\Logging\Logs\File;
use RC\Infrastructure\Logging\Logs\GoogleCloudLogs;
use RC\Infrastructure\Routing\Route\RouteByMethodAndPathPattern;
use RC\Infrastructure\Routing\Route\RouteByTelegramBotCommand;
use RC\Infrastructure\TelegramBot\UserCommand\Start;
use RC\Infrastructure\UserStory\ByRoute;
use RC\Infrastructure\Uuid\RandomUUID;
use RC\UserStories\Sample;
use RC\UserStories\User\PressesStart\PressesStart;

(new EnvironmentDependentEnvFile(
    new ExistentDirPathFromAbsolutePathString(dirname(__FILE__)),
    new DefaultInbound()
))
    ->load();

function entryPoint(ServerRequestInterface $request): ResponseInterface
{
    $logs =
        new EnvironmentDependentLogs(
            new ExistentDirPathFromAbsolutePathString(dirname(__FILE__)),
            new File(
                new ExistentFromDirAndFileName(
                    new FromNestedDirectoryNames(
                        new ExistentDirPathFromAbsolutePathString(dirname(__FILE__)),
                        new PortableFromString('logs')
                    ),
                    new PortableFromString('log.json')
                ),
                new LogId(new RandomUUID())
            ),
            new GoogleCloudLogs(
                'lyrical-bolt-318307',
                'cloudfunctions.googleapis.com%2Fcloud-functions',
                new ExistentFilePathFromAbsolutePathString(__DIR__ . '/deploy/lyrical-bolt-318307-a42c68ffa3c8.json'),
                new LogId(new RandomUUID())
            )
        );
    $transport = new EnvironmentDependentTransport(new ExistentDirPathFromAbsolutePathString(dirname(__FILE__)), $logs);

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
                            function (string $id, string $name, Query $query) use ($logs) {
                                return new Sample($id, $name, $query, $logs);
                            }
                        ],
                        [
                            new RouteByTelegramBotCommand(new Start()),
                            function (array $parsedTelegramMessage) use ($transport, $logs) {
                                return new PressesStart($parsedTelegramMessage, $transport, $logs);
                            }
                        ],
                    ],
                    new WithPathTakenFromQueryParam(
                        'ad_hoc_path',
                        new FromPsrHttpRequest($request)
                    )
                ),
                new FromPsrHttpRequest($request)
            ),
            $request,
            new FallbackResponseBody(),
            $logs
        ))
            ->response();
}
