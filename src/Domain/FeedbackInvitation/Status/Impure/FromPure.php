<?php

declare(strict_types=1);

namespace RC\Domain\FeedbackInvitation\Status\Impure;

use RC\Domain\FeedbackInvitation\Status\Pure\Status as PureStatus;
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

    function exists(): ImpureValue
    {
        return new Successful(new Present($this->pureStatus->exists()));
    }
}