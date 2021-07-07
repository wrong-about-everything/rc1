<?php

declare(strict_types=1);

namespace RC\Infrastructure\Http\Response\Inbound;

use Psr\Http\Message\ResponseInterface;
use RC\Infrastructure\Http\Response\Code\FromInteger;
use RC\Infrastructure\Http\Response\Code;

class FromPsrResponse implements Response
{
    private $code;
    private $headers;
    private $body;

    public function __construct(ResponseInterface $response)
    {
        $this->code = new FromInteger($response->getStatusCode());
        $this->headers = $response->getHeaders();
        $this->body = $response->getBody()->getContents();
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