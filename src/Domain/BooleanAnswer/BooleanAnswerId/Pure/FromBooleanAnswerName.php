<?php

declare(strict_types=1);

namespace RC\Domain\BooleanAnswer\BooleanAnswerId\Pure;

use RC\Domain\BooleanAnswer\BooleanAnswerName\No as JustNo;
use RC\Domain\BooleanAnswer\BooleanAnswerName\Sure;
use RC\Domain\BooleanAnswer\BooleanAnswerName\BooleanAnswerName;
use RC\Domain\BooleanAnswer\BooleanAnswerName\NoMaybeNextTime;
use RC\Domain\BooleanAnswer\BooleanAnswerName\Yes as JustYes;

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
            (new NoMaybeNextTime())->value() => new No(),
            (new JustNo())->value() => new No(),

            (new Sure())->value() => new Yes(),
            (new JustYes())->value() => new Yes(),
        ];
    }
}