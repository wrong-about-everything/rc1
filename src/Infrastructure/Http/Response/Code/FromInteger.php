<?php

declare(strict_types = 1);

namespace RC\Infrastructure\Http\Response\Code;

use RC\Infrastructure\Http\Response\Code;

class FromInteger extends Code
{
    private $code;

    public function __construct(int $code)
    {
        $this->code = $code;
    }

    public function value(): int
    {
        return $this->code;
    }
}