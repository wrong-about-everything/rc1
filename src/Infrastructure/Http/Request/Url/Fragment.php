<?php

declare(strict_types = 1);

namespace RC\Infrastructure\Http\Request\Url;

interface Fragment
{
    public function value(): string;

    public function isSpecified(): bool;
}
