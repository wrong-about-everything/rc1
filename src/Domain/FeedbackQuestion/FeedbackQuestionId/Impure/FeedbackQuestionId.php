<?php

declare(strict_types=1);

namespace RC\Domain\FeedbackQuestion\FeedbackQuestionId\Impure;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface FeedbackQuestionId
{
    public function value(): ImpureValue;
}