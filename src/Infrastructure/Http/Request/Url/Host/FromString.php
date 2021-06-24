<?php

declare(strict_types = 1);

namespace RC\Infrastructure\Http\Request\Url\Host;

use RC\Infrastructure\Http\Request\Url\Host;
use RC\Infrastructure\Http\Request\Url\FromString as UrlFromString;
use Exception;

class FromString implements Host
{
    private $value;

    public function __construct(string $value)
    {
        try {
            new UrlFromString(sprintf('http://%s', $value));
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }

        $this->value = $value;
    }

    public function value(): string
    {
        return rtrim($this->value, '/') . '/';
    }
}
