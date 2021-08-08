<?php

declare(strict_types=1);

namespace RC\Domain\FeedbackInvitation\Status\Impure;

use RC\Domain\FeedbackInvitation\ReadModel\FeedbackInvitation;
use RC\Domain\FeedbackInvitation\Status\Pure\FromInteger;
use RC\Domain\FeedbackInvitation\Status\Pure\NonExistent;
use RC\Infrastructure\ImpureInteractions\ImpureValue;

class FromFeedbackInvitation extends Status
{
    private $invitation;
    private $cached;

    public function __construct(FeedbackInvitation $invitation)
    {
        $this->invitation = $invitation;
        $this->cached = null;
    }

    public function value(): ImpureValue
    {
        return $this->concrete()->value();
    }

    function exists(): ImpureValue
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

    private function doConcrete()
    {
        if (!$this->invitation->value()->isSuccessful()) {
            return new NonSuccessful($this->invitation->value());
        }
        if (!$this->invitation->value()->pure()->isPresent()) {
            return new FromPure(new NonExistent());
        }

        return new FromPure(new FromInteger($this->invitation->value()->pure()->raw()['status']));
    }
}