<?php

declare(strict_types=1);

namespace RC\Infrastructure\Routing;

interface MatchResult
{
    public function matches(): bool;

    public function params(): array;
}