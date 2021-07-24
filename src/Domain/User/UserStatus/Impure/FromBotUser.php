<?php

declare(strict_types=1);

namespace RC\Domain\User\UserStatus\Impure;

use RC\Domain\BotUser\BotUser;
use RC\Domain\User\UserStatus\Pure\FromInteger;
use RC\Domain\User\UserStatus\Pure\NonExistent;
use RC\Infrastructure\ImpureInteractions\ImpureValue;

class FromBotUser extends UserStatus
{
    private $botUser;
    private $cached;

    public function __construct(BotUser $botUser)
    {
        $this->botUser = $botUser;
        $this->cached = null;
    }

    public function value(): ImpureValue
    {
        return $this->cached()->value();
    }

    public function exists(): ImpureValue
    {
        return $this->cached()->exists();
    }

    private function cached()
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doValue();
        }

        return $this->cached;
    }

    private function doValue()
    {
        if (!$this->botUser->value()->isSuccessful()) {
            return new NonSuccessful($this->botUser->value());
        }
        if (!$this->botUser->value()->pure()->isPresent()) {
            return new FromPure(new NonExistent());
        }

        return
            isset($this->botUser->value()->pure()->raw()['status'])
                ? new FromPure(new FromInteger($this->botUser->value()->pure()->raw()['status']))
                : new FromPure(new NonExistent())
            ;
    }
}