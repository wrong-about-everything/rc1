<?php

declare(strict_types=1);

namespace RC\Infrastructure\Http\Request\Url;

interface Scheme
{
    public function value(): string;

    public function isSpecified(): bool;
}
