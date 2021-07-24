<?php

declare(strict_types=1);

namespace RC\Domain\Bot;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface Bot
{
    public function value(): ImpureValue;

    public function exists(): ImpureValue;
}