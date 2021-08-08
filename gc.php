<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Meringue\Timeline\Point\Now;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RC\Activities\Admin\SeesMatches;
use RC\Activities\Cron\AsksForFeedback\AsksForFeedback;
use RC\Activities\Cron\SendsMatchesToParticipants\SendsMatchesToParticipants;
use RC\Domain\Bot\BotId\FromQuery;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use RC\Domain\UserStory\Authorized;
use RC\Domain\UserStory\Body\TelegramFallbackResponseBody;
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
use RC\Infrastructure\Http\Request\Method\Post;
use RC\Infrastructure\Http\Request\Url\Query;
use RC\Infrastructure\Http\Transport\EnvironmentDependentTransport;
use RC\Infrastructure\Logging\LogId;
use RC\Infrastructure\Logging\Logs\EnvironmentDependentLogs;
use RC\Infrastructure\Logging\Logs\File;
use RC\Infrastructure\Logging\Logs\GoogleCloudLogs;
use RC\Infrastructure\Routing\Route\ArbitraryTelegramUserMessageRoute;
use RC\Infrastructure\Routing\Route\MatchingAnyPostRequest;
use RC\Infrastructure\Routing\Route\RouteByMethodAndPathPattern;
use RC\Infrastructure\Routing\Route\RouteByMethodAndPathPatternWithQuery;
use RC\Infrastructure\Routing\Route\RouteByTelegramBotCommand;
use RC\Infrastructure\TelegramBot\UserCommand\Start;
use RC\Infrastructure\UserStory\ByRoute;
use RC\Infrastructure\UserStory\Response\Successful;
use RC\Infrastructure\Uuid\RandomUUID;
use RC\Activities\Cron\InvitesToTakePartInANewRound\InvitesToTakePartInANewRound;
use RC\Activities\Sample;
use RC\Activities\SomeoneSentUnknownPostRequest;
use RC\UserActions\PressesStart\PressesStart;
use RC\UserActions\SendsArbitraryMessage\SendsArbitraryMessage;

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
                            function (array $parsedTelegramMessage, string $botId) use ($transport, $logs) {
                                return new PressesStart($parsedTelegramMessage, $botId, $transport, new ApplicationConnection(), $logs);
                            }
                        ],
                        [
                            new ArbitraryTelegramUserMessageRoute(),
                            function (array $parsedTelegramMessage, string $botId) use ($transport, $logs) {
                                return new SendsArbitraryMessage(new Now(), $parsedTelegramMessage, $botId, $transport, new ApplicationConnection(), $logs);
                            }
                        ],
                        [
                            new RouteByMethodAndPathPatternWithQuery(
                                new Post(),
                                '/cron/invites_to_attend_a_new_round'
                            ),
                            function (Query $query) use ($transport, $logs) {
                                return new InvitesToTakePartInANewRound(new FromQuery($query), $transport, new ApplicationConnection(), $logs);
                            }
                        ],
                        [
                            new RouteByMethodAndPathPatternWithQuery(
                                new Post(),
                                '/cron/sends_matches_to_participants'
                            ),
                            function (Query $query) use ($transport, $logs) {
                                return new SendsMatchesToParticipants(new FromQuery($query), $transport, new ApplicationConnection(), $logs);
                            }
                        ],
                        [
                            new RouteByMethodAndPathPatternWithQuery(
                                new Post(),
                                '/cron/asks_for_feedback'
                            ),
                            function (Query $query) use ($transport, $logs) {
                                return new AsksForFeedback(new FromQuery($query), $transport, new ApplicationConnection(), $logs);
                            }
                        ],
                        [
                            new RouteByMethodAndPathPatternWithQuery(
                                new Get(),
                                '/admin/sees_matches_to_participants'
                            ),
                            function (Query $query) use ($transport, $logs) {
                                return new SeesMatches(new FromQuery($query), new ApplicationConnection(), $logs);
                            }
                        ],
                        [
                            // this one must go the last
                            new MatchingAnyPostRequest(),
                            function (string $message) use ($logs) {
                                return new SomeoneSentUnknownPostRequest($message, $logs);
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
            new Successful(new TelegramFallbackResponseBody()),
            $logs
        ))
            ->response();
}
