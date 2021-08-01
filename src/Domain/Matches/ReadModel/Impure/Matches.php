<?php

declare(strict_types=1);

namespace RC\Domain\Matches\ReadModel\Impure;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface Matches
{
    public function value(): ImpureValue;
}