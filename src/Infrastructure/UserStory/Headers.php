<?php

declare(strict_types=1);

namespace RC\Infrastructure\UserStory;

abstract class Headers
{
    abstract public function value(): array/*<string>*/;

    final public function equals(Headers $code): bool
    {
        return $this->value() === $code->value();
    }
}