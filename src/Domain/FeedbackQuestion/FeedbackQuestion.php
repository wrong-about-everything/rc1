<?php

declare(strict_types=1);

namespace RC\Domain\FeedbackQuestion;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface FeedbackQuestion
{
    public function value(): ImpureValue;
}