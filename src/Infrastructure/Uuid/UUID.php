<?php

declare(strict_types=1);

namespace RC\Infrastructure\Uuid;

interface UUID
{
    public function value(): string;
}