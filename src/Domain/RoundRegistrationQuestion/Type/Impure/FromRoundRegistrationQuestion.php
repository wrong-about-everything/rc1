<?php

declare(strict_types=1);

namespace RC\Domain\RoundRegistrationQuestion\Type\Impure;

use RC\Domain\RoundRegistrationQuestion\RoundRegistrationQuestion;
use RC\Domain\RoundRegistrationQuestion\Type\Pure\FromInteger;
use RC\Domain\RoundRegistrationQuestion\Type\Pure\NonExistent;
use RC\Infrastructure\ImpureInteractions\ImpureValue;

class FromRoundRegistrationQuestion extends RoundRegistrationQuestionType
{
    private $roundRegistrationQuestion;
    private $concrete;

    public function __construct(RoundRegistrationQuestion $roundRegistrationQuestion)
    {
        $this->roundRegistrationQuestion = $roundRegistrationQuestion;
        $this->concrete = null;
    }

    public function value(): ImpureValue
    {
        return $this->concrete()->value();
    }

    public function exists(): ImpureValue
    {
        return $this->concrete()->exists();
    }

    private function concrete()
    {
        if (is_null($this->concrete)) {
            $this->concrete = $this->doConcrete();
        }

        return $this->concrete;
    }

    private function doConcrete()
    {
        if (!$this->roundRegistrationQuestion->value()->isSuccessful()) {
            return new NonSuccessful($this->roundRegistrationQuestion->value());
        }
        if (!$this->roundRegistrationQuestion->value()->pure()->isPresent()) {
            return new FromPure(new NonExistent());
        }

        return new FromPure(new FromInteger($this->roundRegistrationQuestion->value()->pure()->raw()['type']));
    }
}