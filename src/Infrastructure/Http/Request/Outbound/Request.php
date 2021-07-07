<?php

declare(strict_types=1);

namespace RC\Infrastructure\Http\Request\Outbound;

use RC\Infrastructure\Http\Request\Method;
use RC\Infrastructure\Http\Request\Url;

interface Request
{
    public function method(): Method;

    public function url(): Url;

    public function headers(): array/*Map<String, String> || Header[] || string[]*/;

    public function body(): string;
}