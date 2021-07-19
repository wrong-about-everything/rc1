<?php

declare(strict_types=1);

namespace RC\Domain\UserProfileRecordType\Impure;

use RC\Domain\UserProfileRecordType\Pure\UserProfileRecordType as PureUserProfileRecordType;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class FromPure extends UserProfileRecordType
{
    private $pureUserProfileRecordType;

    public function __construct(PureUserProfileRecordType $userProfileRecordType)
    {
        $this->pureUserProfileRecordType = $userProfileRecordType;
    }

    public function value(): ImpureValue
    {
        return new Successful(new Present($this->pureUserProfileRecordType->value()));
    }
}