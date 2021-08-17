<?php

declare(strict_types=1);

namespace RC\Domain\ReplyToUser\ReplyOptions;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface ReplyOptions
{
    public function value(): ImpureValue;
}