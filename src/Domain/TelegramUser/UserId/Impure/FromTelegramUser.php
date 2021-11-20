<?php

declare(strict_types=1);

namespace RC\Domain\TelegramUser\UserId\Impure;

use RC\Domain\TelegramUser\TelegramUser;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class FromTelegramUser extends TelegramUserId
{
    private $user;

    public function __construct(TelegramUser $user)
    {
        $this->user = $user;
    }

    public function value(): ImpureValue
    {
        if (!$this->user->value()->isSuccessful()) {
            return $this->user->value();
        }

        return new Successful(new Present($this->user->value()->pure()->raw()['id']));
    }
}