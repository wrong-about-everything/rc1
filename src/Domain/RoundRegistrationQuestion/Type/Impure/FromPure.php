<?php

declare(strict_types=1);

namespace RC\Domain\RoundRegistrationQuestion\Type\Impure;

use RC\Domain\RoundRegistrationQuestion\Type\Pure\RoundRegistrationQuestionType as PureRoundRegistrationQuestionType;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class FromPure extends RoundRegistrationQuestionType
{
    private $pureRoundRegistrationType;

    public function __construct(PureRoundRegistrationQuestionType $pureRoundRegistrationType)
    {
        $this->pureRoundRegistrationType = $pureRoundRegistrationType;
    }

    public function value(): ImpureValue
    {
        return new Successful(new Present($this->pureRoundRegistrationType->value()));
    }

    public function exists(): ImpureValue
    {
        return new Successful(new Present($this->pureRoundRegistrationType->exists()));
    }
}