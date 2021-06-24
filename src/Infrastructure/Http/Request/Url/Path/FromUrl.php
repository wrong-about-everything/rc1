<?php

declare(strict_types = 1);

namespace RC\Infrastructure\Http\Request\Url\Path;

use \Exception;
use RC\Infrastructure\Http\Request\Url\Path;
use RC\Infrastructure\Http\Request\Url;

class FromUrl implements Path
{
    private $path;

    public function __construct(Url $uri)
    {
        $pathPart = parse_url($uri->value(), PHP_URL_PATH);

        if ($pathPart === false) {
            throw new Exception('Url is incorrect');
        }

        $this->path = $pathPart === null ? new NonSpecified() : new FromString($pathPart);
    }

    public function value(): string
    {
        return $this->path->value();
    }

    public function isSpecified(): bool
    {
        return $this->path->isSpecified();
    }
}