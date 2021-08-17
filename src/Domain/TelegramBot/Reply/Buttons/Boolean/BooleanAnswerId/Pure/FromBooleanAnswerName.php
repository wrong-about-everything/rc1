<?php

declare(strict_types=1);

namespace RC\Domain\TelegramBot\Reply\Buttons\Boolean\BooleanAnswerId\Pure;

use RC\Domain\TelegramBot\Reply\Buttons\Boolean\BooleanAnswerName\No as JustNo;
use RC\Domain\TelegramBot\Reply\Buttons\Boolean\BooleanAnswerName\Sure;
use RC\Domain\TelegramBot\Reply\Buttons\Boolean\BooleanAnswerName\BooleanAnswerName;
use RC\Domain\TelegramBot\Reply\Buttons\Boolean\BooleanAnswerName\NoMaybeNextTime;
use RC\Domain\TelegramBot\Reply\Buttons\Boolean\BooleanAnswerName\Yes as JustYes;

class FromBooleanAnswerName extends BooleanAnswer
{
    private $concrete;

    public function __construct(BooleanAnswerName $booleanAnswerName)
    {
        $this->concrete = isset($this->all()[$booleanAnswerName->value()]) ? $this->all()[$booleanAnswerName->value()] : new NonExistent();
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
            (new NoMaybeNextTime())->value() => new No(),
            (new JustNo())->value() => new No(),

            (new Sure())->value() => new Yes(),
            (new JustYes())->value() => new Yes(),
        ];
    }
}