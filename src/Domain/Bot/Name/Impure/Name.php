<?php

declare(strict_types=1);

namespace RC\Domain\Bot\Name\Impure;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface Name
{
    public function value(): ImpureValue;
}