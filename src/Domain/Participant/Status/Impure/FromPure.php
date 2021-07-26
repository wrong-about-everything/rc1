<?php

declare(strict_types=1);

namespace RC\Domain\Participant\Status\Impure;

use RC\Domain\Participant\Status\Pure\Status as PureStatus;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class FromPure extends Status
{
    private $pureStatus;

    public function __construct(PureStatus $pureStatus)
    {
        $this->pureStatus = $pureStatus;
    }

    public function value(): ImpureValue
    {
        return new Successful(new Present($this->pureStatus->value()));
    }

    public function exists(): ImpureValue
    {
        return new Successful(new Present($this->pureStatus->exists()));
    }
}