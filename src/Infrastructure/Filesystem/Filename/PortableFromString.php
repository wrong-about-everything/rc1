<?php

declare(strict_types=1);

namespace RC\Infrastructure\Filesystem\Filename;

use Exception;
use RC\Infrastructure\Filesystem\Filename;

class PortableFromString extends Filename
{
    private $name;

    public function __construct(string $name)
    {
        if (preg_match('/^[A-Za-z0-9._-]+$/', $name) === 0) {
            throw new Exception('Portable filename must include only A–Za–z0–9\._\-');
        }

        $this->name = $name;
    }

    public function value(): string
    {
        return $this->name;
    }

    public function exists(): bool
    {
        return true;
    }
}