<?php

declare(strict_types=1);

namespace RC\Infrastructure\Routing\Route;

use RC\Domain\TelegramBot\UserCommand\FromTelegramMessage;
use RC\Infrastructure\Http\Request\Inbound\Request;
use RC\Infrastructure\Http\Request\Method\Post;
use RC\Infrastructure\Routing\MatchResult;
use RC\Infrastructure\Routing\MatchResult\Match;
use RC\Infrastructure\Routing\MatchResult\NotMatch;
use RC\Infrastructure\Routing\Route;
use RC\Infrastructure\TelegramBot\UserCommand\UserCommand;

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

        return
            (new FromTelegramMessage($httpRequest->body()))->equals($this->command)
                ?
                    new Match(
                        [json_decode($httpRequest->body(), true)]
                    )
                : new NotMatch()
            ;
    }
}
