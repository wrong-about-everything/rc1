<?php

declare(strict_types=1);

namespace RC\Infrastructure\Uuid;

use Exception;

class FromString implements UUID
{
    private $uuid;

    public function __construct(string $uuid)
    {
        if (!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $uuid)) {
            throw new Exception('UUID is invalid');
        }

        $this->uuid = $uuid;
    }

    public function value(): string
    {
        return $this->uuid;
    }
}