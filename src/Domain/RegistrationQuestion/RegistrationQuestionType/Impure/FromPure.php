<?php

declare(strict_types=1);

namespace RC\Domain\RegistrationQuestion\RegistrationQuestionType\Impure;

use RC\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\RegistrationQuestionType as PureUserProfileRecordType;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class FromPure extends RegistrationQuestionType
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