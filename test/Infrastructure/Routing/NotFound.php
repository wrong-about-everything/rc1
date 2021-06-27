<?php

declare(strict_types=1);

namespace RC\Tests\Infrastructure\Routing;

use RC\Infrastructure\Http\Request\Inbound\Request;
use RC\Infrastructure\Routing\MatchResult;
use RC\Infrastructure\Routing\MatchResult\NotMatch;
use RC\Infrastructure\Routing\Route;

class NotFound implements Route
{
    public function matchResult(Request $httpRequest): MatchResult
    {
        return new NotMatch();
    }
}