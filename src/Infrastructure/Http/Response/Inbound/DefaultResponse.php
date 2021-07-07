<?php

declare(strict_types=1);

namespace RC\Infrastructure\Http\Response\Inbound;

use RC\Infrastructure\Http\Response\Code;

class DefaultResponse implements Response
{
    private $code;
    private $headers;
    private $body;

    public function __construct(Code $code, array $headers, string $body)
    {
        $this->code = $code;
        $this->headers = $headers;
        $this->body = $body;
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function code(): Code
    {
        return $this->code;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function body(): string
    {
        return $this->body;
    }
}