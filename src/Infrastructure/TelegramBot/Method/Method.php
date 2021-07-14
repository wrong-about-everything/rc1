<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\Method;

abstract class Method
{
    abstract public function value(): string;

    final public function equals(Method $method): bool
    {
        return $this->value() === $method->value();
    }
}