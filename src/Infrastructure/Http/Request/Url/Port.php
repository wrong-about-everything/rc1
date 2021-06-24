<?php

declare(strict_types = 1);

namespace RC\Infrastructure\Http\Request\Url;

interface Port
{
    public function value(): int;

    public function isSpecified(): bool;
}
