<?php

declare(strict_types=1);

namespace RC\Domain\MeetingRound\MeetingRoundId\Impure;

use RC\Domain\MeetingRound\MeetingRoundId\Pure\MeetingRoundId as PureMeetingRoundId;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class FromPure extends MeetingRoundId
{
    private $pure;

    public function __construct(PureMeetingRoundId $pureMeetingRoundId)
    {
        $this->pure = $pureMeetingRoundId;
    }

    public function value(): ImpureValue
    {
        return new Successful(new Present($this->pure->value()));
    }

    public function exists(): ImpureValue
    {
        return new Successful(new Present($this->pure->exists()));
    }
}