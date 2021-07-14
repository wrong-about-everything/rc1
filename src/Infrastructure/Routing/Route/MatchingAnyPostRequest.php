<?php

declare(strict_types=1);

namespace RC\Infrastructure\Routing\Route;

use RC\Infrastructure\Http\Request\Inbound\Request;
use RC\Infrastructure\Http\Request\Method\Post;
use RC\Infrastructure\Routing\MatchResult;
use RC\Infrastructure\Routing\MatchResult\Match;
use RC\Infrastructure\Routing\MatchResult\NotMatch;
use RC\Infrastructure\Routing\Route;

class MatchingAnyPostRequest implements Route
{
    public function matchResult(Request $httpRequest): MatchResult
    {
        if (!$httpRequest->method()->equals(new Post())) {
            return new NotMatch();
        }

        return new Match([$httpRequest->body()]);
    }
}
