<?php

declare(strict_types=1);

namespace RC\Domain\About\Impure;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface About
{
    public function value(): ImpureValue;

    public function empty(): ImpureValue;

    public function exists(): ImpureValue;
}