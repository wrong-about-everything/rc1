<?php

declare(strict_types=1);

namespace RC\Domain\About\Pure;

interface About
{
    public function value(): string;

    public function empty(): bool;

    public function exists(): bool;
}