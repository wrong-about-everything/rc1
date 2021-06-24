<?php

declare(strict_types = 1);

namespace RC\Infrastructure\Http\Request\Url;

interface Query
{
    public function value(): string;

    public function isSpecified(): bool;
}
