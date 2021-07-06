<?php

declare(strict_types=1);

namespace RC\Infrastructure\Http\Request\Url\Scheme;

use Exception;
use RC\Infrastructure\Http\Request\Url\Scheme;

class FromString implements Scheme
{
    private $scheme;

    public function __construct(string $scheme)
    {
        if (!in_array($scheme, [(new Http())->value(), (new Https())->value(), ])) {
            throw new Exception(sprintf('Unknown scheme given: %s', $scheme));
        }

        $this->scheme = $scheme;
    }

    public function value(): string
    {
        return $this->scheme;
    }

    public function isSpecified(): bool
    {
        return true;
    }
}
