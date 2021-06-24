<?php

declare(strict_types=1);

namespace RC\Infrastructure\Http\Response\Header\ContentTypeValue;

abstract class Value
{
    abstract public function value(): string;

    final public function equals(Value $value)
    {
        return $this->value() === $value->value();
    }
}