<?php

declare(strict_types=1);

namespace RC\Infrastructure\Routing\Route;

use RC\Infrastructure\Http\Request\Inbound\Request;
use RC\Infrastructure\Http\Request\Method;
use RC\Infrastructure\Http\Request\Url\Query\FromUrl as QueryFromUrl;
use RC\Infrastructure\Routing\MatchResult;
use RC\Infrastructure\Routing\MatchResult\CombinedMatch;
use RC\Infrastructure\Routing\MatchResult\Match;
use RC\Infrastructure\Routing\Route;

class RouteByMethodAndPathPatternWithQuery implements Route
{
    private $method;
    private $pathPattern;

    public function __construct(Method $method, string $pathPattern)
    {
        $this->method = $method;
        $this->pathPattern = $pathPattern;
    }

    public function matchResult(Request $httpRequest): MatchResult
    {
        $matchResult = (new RouteByMethodAndPathPattern($this->method, $this->pathPattern))->matchResult($httpRequest);
        if (!$matchResult->matches()) {
            return $matchResult;
        }

        return new CombinedMatch($matchResult, new Match([new QueryFromUrl($httpRequest->url())]));
    }
}
