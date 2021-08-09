<?php

declare(strict_types=1);

namespace RC\Domain\FeedbackQuestion\FeedbackQuestionId\Pure;

interface FeedbackQuestionId
{
    public function value(): string;
}