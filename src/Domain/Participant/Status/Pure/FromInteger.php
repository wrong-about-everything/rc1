<?php

declare(strict_types=1);

namespace RC\Domain\Participant\Status\Pure;

use RC\Domain\BotUser\UserStatus\Pure\RegistrationIsInProgress;

class FromInteger extends Status
{
    private $concrete;

    public function __construct(int $status)
    {
        $this->concrete = $this->all()[$status] ?? new NonExistent();
    }

    public function value(): int
    {
        return $this->concrete->value();
    }

    public function exists(): bool
    {
        return $this->concrete->exists();
    }

    private function all()
    {
        return [
            (new RegistrationIsInProgress())->value() => new RegistrationIsInProgress(),
            (new Registered())->value() => new Registered(),
        ];
    }
}