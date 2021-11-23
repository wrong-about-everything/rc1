<?php

declare(strict_types=1);

namespace RC\Domain\Bot\SupportBotName\Impure;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface SupportBotName
{
    public function value(): ImpureValue;
}