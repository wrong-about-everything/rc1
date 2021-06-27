<?php

declare(strict_types=1);

namespace RC\Infrastructure\Routing\MatchResult;

use RC\Infrastructure\Routing\MatchResult;

class Match implements MatchResult
{
    private $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    public function matches(): bool
    {
        return true;
    }

    public function params(): array
    {
        return $this->params;
    }
}