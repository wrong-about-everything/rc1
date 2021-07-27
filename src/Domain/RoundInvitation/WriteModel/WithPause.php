<?php

declare(strict_types=1);

namespace RC\Domain\RoundInvitation\WriteModel;

use RC\Domain\RoundInvitation\WriteModel\Invitation;
use RC\Infrastructure\ImpureInteractions\ImpureValue;

class WithPause implements Invitation
{
    private $meetingRoundInvitation;
    private $microseconds;
    private $cached;

    public function __construct(Invitation $meetingRoundInvitation, int $microseconds)
    {
        $this->meetingRoundInvitation = $meetingRoundInvitation;
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
        $r = $this->meetingRoundInvitation->value();
        usleep($this->microseconds);
        return $r;
    }
}