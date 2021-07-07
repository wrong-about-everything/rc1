<?php

declare(strict_types=1);

namespace RC\Infrastructure\Http\Response\Inbound;

use Psr\Http\Message\ResponseInterface;
use RC\Infrastructure\Http\Response\Code\FromInteger as IFromInteger;
use RC\Infrastructure\Http\Response\Code;
use Exception;

class Unavailable implements Response
{
    public function isAvailable(): bool
    {
        return false;
    }

    public function code(): Code
    {
        throw new Exception('Response is unavailable, so is an http code.');
    }

    public function headers(): array
    {
        throw new Exception('Response is unavailable, so are http headers.');
    }

    public function body(): string
    {
        throw new Exception('Response is unavailable, so is an http body.');
    }

}