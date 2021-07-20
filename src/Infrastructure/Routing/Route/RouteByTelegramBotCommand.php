<?php

declare(strict_types=1);

namespace RC\Infrastructure\Routing\Route;

use RC\Domain\TelegramBot\UserCommand\FromTelegramMessage;
use RC\Infrastructure\Http\Request\Inbound\Request;
use RC\Infrastructure\Http\Request\Method\Post;
use RC\Infrastructure\Http\Request\Url\Query\FromUrl;
use RC\Infrastructure\Routing\MatchResult;
use RC\Infrastructure\Routing\MatchResult\Match;
use RC\Infrastructure\Routing\MatchResult\NotMatch;
use RC\Infrastructure\Routing\Route;
use RC\Infrastructure\TelegramBot\UserCommand\UserCommand;
use RC\Infrastructure\UserStory\Response\Unauthorized;

class RouteByTelegramBotCommand implements Route
{
    private $command;

    public function __construct(UserCommand $command)
    {
        $this->command = $command;
    }

    public function matchResult(Request $httpRequest): MatchResult
    {
        if (!$httpRequest->method()->equals(new Post())) {
            return new NotMatch();
        }

        $userCommand = new FromTelegramMessage($httpRequest->body());

        return
            $userCommand->exists() && $userCommand->equals($this->command)
                ?
                    new Match(
                        [json_decode($httpRequest->body(), true), $this->botId($httpRequest)]
                    )
                : new NotMatch()
            ;
    }

    private function botId(Request $httpRequest)
    {
        parse_str(
            (new FromUrl($httpRequest->url()))->value(),
            $parsedQuery
        );
        return $parsedQuery['secret_smile'];
    }
}