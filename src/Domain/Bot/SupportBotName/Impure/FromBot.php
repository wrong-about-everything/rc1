<?php

declare(strict_types=1);

namespace RC\Domain\Bot\SupportBotName\Impure;

use RC\Domain\Bot\Bot;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class FromBot implements SupportBotName
{
    private $bot;

    public function __construct(Bot $bot)
    {
        $this->bot = $bot;
    }

    public function value(): ImpureValue
    {
        if (!$this->bot->value()->isSuccessful()) {
            return $this->bot->value();
        }

        return new Successful(new Present($this->bot->value()->pure()->raw()['support_bot_name']));
    }
}