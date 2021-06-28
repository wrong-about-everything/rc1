<?php

declare(strict_types=1);

namespace RC\Infrastructure\Uuid;

class Fixed implements UUID
{
    public function value(): string
    {
        return '003729d6-330c-4123-b856-d5196812d509';
    }
}