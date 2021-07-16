<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\UserId;

use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class FromInteger extends TelegramUserId
{
    private $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function value(): ImpureValue
    {
        return new Successful(new Present($this->id));
    }

    public function exists(): bool
    {
        return true;
    }
}