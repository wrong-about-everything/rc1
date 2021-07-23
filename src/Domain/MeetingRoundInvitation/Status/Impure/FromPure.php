<?php

declare(strict_types=1);

namespace RC\Domain\MeetingRoundInvitation\Status\Impure;

use RC\Domain\MeetingRoundInvitation\Status\Pure\Status as PureStatus;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class FromPure extends Status
{
    private $status;

    public function __construct(PureStatus $status)
    {
        $this->status = $status;
    }

    public function exists(): ImpureValue
    {
        return new Successful(new Present($this->status->exists()));
    }

    public function value(): ImpureValue
    {
        return new Successful(new Present($this->status->value()));
    }
}