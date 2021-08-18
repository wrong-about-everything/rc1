<?php

declare(strict_types=1);

namespace RC\Domain\About\Pure;

use Exception;

class NonEmpty implements About
{
    private $about;

    public function __construct(string $about)
    {
        if ($about === '') {
            throw new Exception('About is empty');
        }

        $this->about = $about;
    }

    public function value(): string
    {
        return $this->about;
    }

    public function empty(): bool
    {
        return false;
    }

    public function exists(): bool
    {
        return true;
    }
}
