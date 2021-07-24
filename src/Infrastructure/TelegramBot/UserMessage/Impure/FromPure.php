<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\UserMessage\Impure;

use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\TelegramBot\UserMessage\Pure\UserMessage as PureUserMessage;

class FromPure implements UserMessage
{
    private $pureUserMessage;

    public function __construct(PureUserMessage $pureUserMessage)
    {
        $this->pureUserMessage = $pureUserMessage;
    }

    public function value(): ImpureValue
    {
        return new Successful(new Present($this->pureUserMessage->value()));
    }

    public function exists(): ImpureValue
    {
        return new Successful(new Present($this->pureUserMessage->exists()));
    }
}