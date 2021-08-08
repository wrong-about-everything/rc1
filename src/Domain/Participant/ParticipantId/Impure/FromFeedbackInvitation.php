<?php

declare(strict_types=1);

namespace RC\Domain\Participant\ParticipantId\Impure;

use RC\Domain\FeedbackInvitation\ReadModel\FeedbackInvitation;
use RC\Domain\Participant\ParticipantId\Pure\FromString;
use RC\Domain\Participant\ReadModel\NonExistent;
use RC\Domain\Participant\ReadModel\NonSuccessful;
use RC\Infrastructure\ImpureInteractions\ImpureValue;

class FromFeedbackInvitation implements ParticipantId
{
    private $feedbackInvitation;
    private $cached;

    public function __construct(FeedbackInvitation $feedbackInvitation)
    {
        $this->feedbackInvitation = $feedbackInvitation;
        $this->cached = null;
    }

    public function value(): ImpureValue
    {
        return $this->concrete()->value();
    }

    public function exists(): ImpureValue
    {
        return $this->concrete()->exists();
    }

    private function concrete()
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doConcrete();
        }

        return $this->cached;
    }

    private function doConcrete(): ParticipantId
    {
        if (!$this->feedbackInvitation->value()->isSuccessful()) {
            return new FromReadModelParticipant(new NonSuccessful($this->feedbackInvitation->value()));
        }
        if (!$this->feedbackInvitation->value()->pure()->isPresent()) {
            return new FromReadModelParticipant(new NonExistent());
        }

        return new FromPure(new FromString($this->feedbackInvitation->value()->pure()->raw()['participant_id']));
    }
}