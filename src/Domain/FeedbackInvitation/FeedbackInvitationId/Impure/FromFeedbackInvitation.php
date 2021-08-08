<?php

declare(strict_types=1);

namespace RC\Domain\FeedbackInvitation\FeedbackInvitationId\Impure;

use RC\Domain\FeedbackInvitation\FeedbackInvitationId\Pure\FromString;
use RC\Domain\FeedbackInvitation\ReadModel\FeedbackInvitation;
use RC\Domain\FeedbackInvitation\ReadModel\NonExistent;
use RC\Domain\FeedbackInvitation\ReadModel\NonSuccessful;
use RC\Infrastructure\ImpureInteractions\ImpureValue;

class FromFeedbackInvitation implements FeedbackInvitationId
{
    private $feedbackInvitation;
    private $concrete;

    public function __construct(FeedbackInvitation $feedbackInvitation)
    {
        $this->feedbackInvitation = $feedbackInvitation;
        $this->concrete = null;
    }

    public function value(): ImpureValue
    {
        return $this->concrete()->value();
    }

    public function exists(): ImpureValue
    {
        return $this->concrete()->exists();
    }

    private function concrete(): FeedbackInvitationId
    {
        if (is_null($this->concrete)) {
            $this->concrete = $this->doConcrete();
        }

        return $this->concrete;
    }

    private function doConcrete()
    {
        if (!$this->feedbackInvitation->value()->isSuccessful()) {
            return new FromFeedbackInvitation(new NonSuccessful($this->feedbackInvitation->value()));
        }
        if (!$this->feedbackInvitation->value()->pure()->isPresent()) {
            return new FromFeedbackInvitation(new NonExistent());
        }

        return new FromPure(new FromString($this->feedbackInvitation->value()->pure()->raw()['id']));
    }
}