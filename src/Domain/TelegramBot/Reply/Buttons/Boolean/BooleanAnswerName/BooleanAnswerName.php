<?php

declare(strict_types=1);

namespace RC\Domain\TelegramBot\Reply\Buttons\Boolean\BooleanAnswerName;

abstract class BooleanAnswerName
{
    abstract public function value(): string;

    abstract public function exists(): bool;

    final public function equals(BooleanAnswerName $BooleanAnswerName): bool
    {
        return $this->value() === $BooleanAnswerName->value();
    }
}