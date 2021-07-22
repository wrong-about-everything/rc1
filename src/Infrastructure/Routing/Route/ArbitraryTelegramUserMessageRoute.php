<?php

declare(strict_types=1);

namespace RC\Infrastructure\Routing\Route;

use RC\Domain\BotId\FromQuery;
use RC\Infrastructure\TelegramBot\UserMessage\FromTelegramMessage;
use RC\Infrastructure\Http\Request\Inbound\Request;
use RC\Infrastructure\Http\Request\Method\Post;
use RC\Infrastructure\Http\Request\Url\Query\FromUrl;
use RC\Infrastructure\Routing\MatchResult;
use RC\Infrastructure\Routing\MatchResult\Match;
use RC\Infrastructure\Routing\MatchResult\NotMatch;
use RC\Infrastructure\Routing\Route;

class ArbitraryTelegramUserMessageRoute implements Route
{
    public function matchResult(Request $httpRequest): MatchResult
    {
        if (!$httpRequest->method()->equals(new Post())) {
            return new NotMatch();
        }

        $userMessage = new FromTelegramMessage($httpRequest->body());

        return
            $userMessage->exists()
                ?
                    new Match(
                        [json_decode($httpRequest->body(), true), $this->botId($httpRequest)->value()]
                    )
                : new NotMatch()
            ;
    }

    private function botId(Request $httpRequest)
    {
        return new FromQuery(new FromUrl($httpRequest->url()));
    }
}
