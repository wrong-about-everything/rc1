<?php

declare(strict_types=1);

namespace RC\Domain\BotUser\Id\Impure;

use RC\Domain\BotUser\ReadModel\BotUser;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class FromReadModelBotUser implements BotUserId
{
    private $botUser;

    public function __construct(BotUser $botUser)
    {
        $this->botUser = $botUser;
    }

    public function value(): ImpureValue
    {
        if (!$this->botUser->value()->isSuccessful()) {
            return $this->botUser->value();
        }

        return new Successful(new Present($this->botUser->value()->pure()->raw()['id']));
    }
}