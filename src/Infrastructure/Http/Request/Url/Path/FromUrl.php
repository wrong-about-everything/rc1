<?php

declare(strict_types = 1);

namespace RC\Infrastructure\Http\Request\Url\Path;

use RC\Infrastructure\Http\Request\Url\Path;
use RC\Infrastructure\Http\Request\Url;

class FromUrl implements Path
{
    private $path;

    public function __construct(Url $uri)
    {
        $pathPart = parse_url($uri->value(), PHP_URL_PATH);

        $this->path = new FromString($pathPart ?? '');
    }

    public function value(): string
    {
        return $this->path->value();
    }
}