<?php

declare(strict_types=1);

namespace RC\Domain\Bot;

use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class FromArray implements Bot
{
    private $array;

    public function __construct(array $array)
    {
        $this->array = $array;
    }

    public function value(): ImpureValue
    {
        return new Successful(new Present($this->array));
    }

    public function exists(): ImpureValue
    {
        return new Successful(new Present(true));
    }
}