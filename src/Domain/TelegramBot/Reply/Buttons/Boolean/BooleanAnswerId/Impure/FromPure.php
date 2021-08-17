<?php

declare(strict_types=1);

namespace RC\Domain\TelegramBot\Reply\Buttons\Boolean\BooleanAnswerId\Impure;

use RC\Domain\TelegramBot\Reply\Buttons\Boolean\BooleanAnswerId\Pure\BooleanAnswer as PureBooleanAnswer;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class FromPure extends BooleanAnswer
{
    private $booleanAnswer;

    public function __construct(PureBooleanAnswer $booleanAnswer)
    {
        $this->booleanAnswer = $booleanAnswer;
    }

    public function value(): ImpureValue
    {
        return new Successful(new Present($this->booleanAnswer->value()));
    }

    public function exists(): ImpureValue
    {
        return new Successful(new Present($this->booleanAnswer->exists()));
    }
}