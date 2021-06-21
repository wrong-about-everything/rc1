<?php

declare(strict_types=1);

namespace RC\Infrastructure\ImpureInteractions;

interface PureValue
{
    public function isPresent(): bool;

    public function raw();
}