<?php

declare(strict_types=1);

namespace RC\Domain\FeedbackInvitation\WriteModel;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

class WithPause implements FeedbackInvitation
{
    private $feedbackInvitation;
    private $microseconds;
    private $cached;

    public function __construct(FeedbackInvitation $feedbackInvitation, int $microseconds)
    {
        $this->feedbackInvitation = $feedbackInvitation;
        $this->microseconds = $microseconds;
        $this->cached = null;
    }

    public function value(): ImpureValue
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doValue();
        }

        return $this->cached;
    }

    private function doValue(): ImpureValue
    {
        $r = $this->feedbackInvitation->value();
        usleep($this->microseconds);
        return $r;
    }
}