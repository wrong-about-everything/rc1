<?php

declare(strict_types = 1);

namespace RC\Infrastructure\Http\Request\Url;

interface Path
{
    public function value(): string;
}
