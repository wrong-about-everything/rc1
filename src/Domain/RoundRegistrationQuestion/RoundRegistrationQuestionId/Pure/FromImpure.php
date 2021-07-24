<?php

declare(strict_types=1);

namespace RC\Domain\RoundRegistrationQuestion\RoundRegistrationQuestionId\Pure;

use RC\Domain\RoundRegistrationQuestion\RoundRegistrationQuestionId\Impure\RoundRegistrationQuestionId as ImpureRoundRegistrationQuestionId;

class FromImpure implements RoundRegistrationQuestionId
{
    private $roundRegistrationQuestionId;

    public function __construct(ImpureRoundRegistrationQuestionId $roundRegistrationQuestionId)
    {
        $this->roundRegistrationQuestionId = $roundRegistrationQuestionId;
    }

    public function value(): string
    {
        return $this->roundRegistrationQuestionId->value()->pure()->raw();
    }
}