<?php

declare(strict_types=1);

namespace RC\Infrastructure\Routing\MatchResult;

use RC\Infrastructure\Routing\MatchResult;

class MatchWithNoParams implements MatchResult
{
    public function matches(): bool
    {
        return true;
    }

    public function params(): array
    {
        return [];
    }
}