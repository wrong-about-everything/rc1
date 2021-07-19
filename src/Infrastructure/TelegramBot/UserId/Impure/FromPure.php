<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\UserId\Impure;

use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\TelegramBot\UserId\Impure\TelegramUserId as ImpureTelegramUserId;
use RC\Infrastructure\TelegramBot\UserId\Pure\TelegramUserId as PureTelegramUserId;

class FromPure extends ImpureTelegramUserId
{
    private $pureTelegramUserId;

    public function __construct(PureTelegramUserId $telegramUserId)
    {
        $this->pureTelegramUserId = $telegramUserId;
    }

    public function value(): ImpureValue
    {
        return new Successful(new Present($this->pureTelegramUserId->value()));
    }

    public function exists(): bool
    {
        return $this->pureTelegramUserId->exists();
    }
}