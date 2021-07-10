<?php

declare(strict_types=1);

namespace RC\Infrastructure\ImpureInteractions;

interface ImpureValue
{
    public function isSuccessful(): bool;

    public function pure(): PureValue;

    public function error(): Error;
}