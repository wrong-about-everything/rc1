<?php

declare(strict_types=1);

namespace RC\Infrastructure\Routing;

use RC\Infrastructure\Http\Request\Inbound\Request;

interface Route
{
    public function matchResult(Request $httpRequest): MatchResult;
}
