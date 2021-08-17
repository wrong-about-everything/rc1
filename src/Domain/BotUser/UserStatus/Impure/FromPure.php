<?php

declare(strict_types=1);

namespace RC\Domain\BotUser\UserStatus\Impure;

use RC\Domain\BotUser\UserStatus\Pure\UserStatus as PureUserStatus;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class FromPure extends UserStatus
{
    private $pureUserStatus;

    public function __construct(PureUserStatus $pureUserStatus)
    {
        $this->pureUserStatus = $pureUserStatus;
    }

    public function value(): ImpureValue
    {
        return new Successful(new Present($this->pureUserStatus->value()));
    }

    public function exists(): ImpureValue
    {
        return new Successful(new Present($this->pureUserStatus->exists()));
    }
}