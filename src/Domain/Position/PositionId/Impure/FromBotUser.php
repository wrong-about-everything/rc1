<?php

declare(strict_types=1);

namespace RC\Domain\Position\PositionId\Impure;

use RC\Domain\BotUser\ReadModel\BotUser;
use RC\Domain\Position\PositionId\Pure\FromInteger;
use RC\Domain\Position\PositionId\Pure\NonExistent;
use RC\Infrastructure\ImpureInteractions\ImpureValue;

class FromBotUser extends Position
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
        return $this->concrete()->value();
    }

    public function exists(): ImpureValue
    {
        return $this->concrete()->exists();
    }

    private function concrete(): Position
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doConcrete();
        }

        return $this->cached;
    }

    private function doConcrete(): Position
    {
        if (!$this->botUser->value()->isSuccessful()) {
            return new NonSuccessful($this->botUser->value());
        }

        return
            isset($this->botUser->value()->pure()->raw()['position'])
                ? new FromPure(new FromInteger($this->botUser->value()->pure()->raw()['position']))
                : new FromPure(new NonExistent())
            ;
    }
}