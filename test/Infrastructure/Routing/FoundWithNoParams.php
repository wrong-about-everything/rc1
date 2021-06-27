<?php

declare(strict_types=1);

namespace RC\Tests\Infrastructure\Routing;

use RC\Infrastructure\Http\Request\Inbound\Request;
use RC\Infrastructure\Routing\MatchResult;
use RC\Infrastructure\Routing\MatchResult\MatchWithNoParams;
use RC\Infrastructure\Routing\Route;

class FoundWithNoParams implements Route
{
    public function matchResult(Request $httpRequest): MatchResult
    {
        return new MatchWithNoParams();
    }
}