<?php

declare(strict_types=1);

namespace RC\Domain\RoundRegistrationQuestion\RoundRegistrationQuestionId\Impure;

use RC\Domain\RoundRegistrationQuestion\RoundRegistrationQuestion;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class FromRoundRegistrationQuestion implements RoundRegistrationQuestionId
{
    private $roundRegistrationQuestion;

    public function __construct(RoundRegistrationQuestion $roundRegistrationQuestion)
    {
        $this->roundRegistrationQuestion = $roundRegistrationQuestion;
    }

    public function value(): ImpureValue
    {
        if (!$this->roundRegistrationQuestion->value()->isSuccessful()) {
            return $this->roundRegistrationQuestion->value();
        }

        return new Successful(new Present($this->roundRegistrationQuestion->value()->pure()->raw()['id']));
    }
}