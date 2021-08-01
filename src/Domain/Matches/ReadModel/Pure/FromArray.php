<?php

declare(strict_types=1);

namespace RC\Domain\Matches\ReadModel\Pure;

class FromArray implements Matches
{
    private $matches;

    public function __construct(array $matches)
    {
        $this->matches = $matches;
    }

    public function value(): array
    {
        return $this->matches;
    }
}