<?php

declare(strict_types=1);

namespace RC\Domain\FeedbackQuestion\FeedbackQuestionId\Impure;

use RC\Domain\FeedbackQuestion\FeedbackQuestion;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class FromFeedbackQuestion implements FeedbackQuestionId
{
    private $feedbackQuestion;

    public function __construct(FeedbackQuestion $feedbackQuestion)
    {
        $this->feedbackQuestion = $feedbackQuestion;
    }

    public function value(): ImpureValue
    {
        if (!$this->feedbackQuestion->value()->isSuccessful() || !$this->feedbackQuestion->value()->pure()->isPresent()) {
            return $this->feedbackQuestion->value();
        }

        return new Successful(new Present($this->feedbackQuestion->value()->pure()->raw()['id']));
    }
}