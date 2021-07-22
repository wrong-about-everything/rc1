<?php

declare(strict_types=1);

namespace RC\Domain\BooleanAnswer\BooleanAnswerName;

use RC\Domain\BooleanAnswer\BooleanAnswerId\Pure\Yes;
use RC\Domain\BooleanAnswer\BooleanAnswerId\Pure\BooleanAnswer;
use RC\Domain\BooleanAnswer\BooleanAnswerId\Pure\No;

class FromBooleanAnswer extends BooleanAnswerName
{
    private $booleanAnswerName;

    public function __construct(BooleanAnswer $booleanAnswer)
    {
        $this->booleanAnswerName = $this->concrete($booleanAnswer);
    }

    public function value(): string
    {
        return $this->booleanAnswerName->value();
    }

    public function exists(): bool
    {
        return $this->booleanAnswerName->exists();
    }

    private function concrete(BooleanAnswer $booleanAnswer): BooleanAnswerName
    {
        return [
            (new No())->value() => new No(),
            (new Yes())->value() => new Yes(),
        ][$booleanAnswer->value()] ?? new NonExistent();
    }
}