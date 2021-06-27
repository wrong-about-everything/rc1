<?php

declare(strict_types=1);

namespace RC\Infrastructure\Routing\MatchResult;

use Exception;
use RC\Infrastructure\Routing\MatchResult;

class NotMatch implements MatchResult
{
    public function matches(): bool
    {
        return false;
    }

    public function params(): array
    {
        throw new Exception('This is not a match. There can not be any params.');
    }
}