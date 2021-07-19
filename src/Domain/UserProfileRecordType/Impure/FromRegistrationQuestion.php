<?php

declare(strict_types=1);

namespace RC\Domain\UserProfileRecordType\Impure;

use RC\Domain\RegistrationQuestion\RegistrationQuestion;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class FromRegistrationQuestion extends UserProfileRecordType
{
    private $registrationQuestion;

    public function __construct(RegistrationQuestion $registrationQuestion)
    {
        $this->registrationQuestion = $registrationQuestion;
    }

    public function value(): ImpureValue
    {
        if (!$this->registrationQuestion->value()->isSuccessful()) {
            return $this->registrationQuestion->value();
        }

        return new Successful(new Present($this->registrationQuestion->value()->pure()->raw()['profile_record_type']));
    }
}