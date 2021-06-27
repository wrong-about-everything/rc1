<?php

declare(strict_types=1);

namespace RC\Tests\Infrastructure\Routing;

use RC\Infrastructure\Http\Request\Inbound\Request;
use RC\Infrastructure\Routing\MatchResult;
use RC\Infrastructure\Routing\MatchResult\Match;
use RC\Infrastructure\Routing\Route;

class FoundWithParams implements Route
{
    private $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    public function matchResult(Request $httpRequest): MatchResult
    {
        return new Match($this->params);
    }
}