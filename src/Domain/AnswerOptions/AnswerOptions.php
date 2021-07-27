<?php

declare(strict_types=1);

namespace RC\Domain\AnswerOptions;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface AnswerOptions
{
    public function value(): ImpureValue;
}