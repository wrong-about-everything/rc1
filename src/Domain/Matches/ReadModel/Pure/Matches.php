<?php

declare(strict_types=1);

namespace RC\Domain\Matches\ReadModel\Pure;

interface Matches
{
    /**
     * Matches and dropouts:
     * [
     *      'matches' => [[1, 2], ... ],
     *      'dropouts' => [7, 8, 9, ...],
     * ]
     */
    public function value(): array;
}