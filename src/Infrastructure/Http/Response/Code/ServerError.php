<?php

declare(strict_types = 1);

namespace RC\Infrastructure\Http\Response\Code;

use RC\Infrastructure\Http\Response\Code;

class ServerError extends Code
{
    public function value(): int
    {
        return 500;
    }
}