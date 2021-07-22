<?php

declare(strict_types=1);

namespace RC\Domain\BooleanAnswer\BooleanAnswerName;

abstract class BooleanAnswerName
{
    abstract public function value(): string;

    abstract public function exists(): bool;

    final public function equals(BooleanAnswerName $BooleanAnswerName): bool
    {
        return $this->value() === $BooleanAnswerName->value();
    }
}