<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\UserMessage;

use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class FromString implements UserMessage
{
    private $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function value(): ImpureValue
    {
        return new Successful(new Present($this->value));
    }

    public function exists(): bool
    {
        return true;
    }
}