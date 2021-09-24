<?php

declare(strict_types=1);

namespace RC\Domain\BotUser\Id\Impure;

use RC\Domain\BotUser\WriteModel\BotUser;
use RC\Infrastructure\ImpureInteractions\ImpureValue;

class FromWriteModelBotUser implements BotUserId
{
    private $botUser;

    public function __construct(BotUser $botUser)
    {
        $this->botUser = $botUser;
    }

    public function value(): ImpureValue
    {
        return $this->botUser->value();
    }
}