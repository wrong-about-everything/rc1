<?php

declare(strict_types=1);

namespace RC\Infrastructure\Http\Request\Inbound;

use RC\Infrastructure\Http\Request\Method;
use RC\Infrastructure\Http\Request\Url;

interface Request
{
    public function method(): Method;

    public function url(): Url;

    public function headers(): array/*Map<String, String>*/;

    public function body(): string;
}