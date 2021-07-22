<?php

declare(strict_types=1);

namespace RC\Domain\BooleanAnswer\BooleanAnswerId\Pure;

use RC\Domain\BooleanAnswer\BooleanAnswerName\Yes;
use RC\Domain\BooleanAnswer\BooleanAnswerName\BooleanAnswerName;
use RC\Domain\BooleanAnswer\BooleanAnswerName\No;

class FromBooleanAnswerName extends BooleanAnswer
{
    private $concrete;

    public function __construct(BooleanAnswerName $booleanAnswerName)
    {
        $this->concrete = isset($this->all()[$booleanAnswerName->value()]) ? $this->all()[$booleanAnswerName->value()] : new NonExistent();
    }

    public function value(): int
    {
        return $this->concrete->value();
    }

    public function exists(): bool
    {
        return $this->concrete->exists();
    }

    private function all()
    {
        return [
            (new No())->value() => new No(),
            (new Yes())->value() => new Yes(),
        ];
    }
}