<?php

declare(strict_types=1);

namespace RC\Infrastructure\Http\Request\Inbound;

use RC\Infrastructure\Http\Request\Method;
use RC\Infrastructure\Http\Request\Url;

interface Request
{
    public function method(): Method;

    public function url(): Url;

    /**
     * @info: Do I really need Map<String, String> return type?
     * This is an request used internally, it should have a format that is comfortable for me.
     * That differs from a Response interface: it should have the most flexible format,
     * because I should be able to convert it to any external format (e.g., psr7).
     */
    public function headers(): array/*Map<String, String> || Header[] || string[]*/;

    public function body(): string;
}