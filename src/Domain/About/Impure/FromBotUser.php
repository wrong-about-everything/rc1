<?php

declare(strict_types=1);

namespace RC\Domain\About\Impure;

use RC\Domain\About\Pure\Emptie;
use RC\Domain\About\Pure\FromString;
use RC\Domain\About\Pure\NonExistent;
use RC\Domain\BotUser\BotUser;
use RC\Infrastructure\ImpureInteractions\ImpureValue;

class FromBotUser implements About
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

    public function empty(): ImpureValue
    {
        return $this->concrete()->empty();
    }

    public function exists(): ImpureValue
    {
        return $this->concrete()->exists();
    }

    private function concrete(): About
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doConcrete();
        }

        return $this->cached;
    }

    private function doConcrete(): About
    {
        if (!$this->botUser->value()->isSuccessful()) {
            return new NonSuccessful($this->botUser->value());
        }
        if (!$this->botUser->value()->pure()->isPresent()) {
            return new FromPure(new NonExistent());
        }
        if (is_null($this->botUser->value()->pure()->raw()['about'])) {
            return new FromPure(new Emptie());
        }

        return new FromPure(new FromString($this->botUser->value()->pure()->raw()['about']));
    }
}
