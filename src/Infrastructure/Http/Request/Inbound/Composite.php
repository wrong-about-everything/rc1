<?php

declare(strict_types=1);

namespace RC\Infrastructure\Http\Request\Inbound;

use RC\Infrastructure\Http\Request\Method;
use RC\Infrastructure\Http\Request\Url;

class Composite implements Request
{
    private $method;
    private $url;
    private $headers;
    private $body;

    public function __construct(Method $method, Url $url, array $headers, string $body)
    {
        $this->method = $method;
        $this->url = $url;
        $this->headers = $headers;
        $this->body = $body;
    }

    public function method(): Method
    {
        return $this->method;
    }

    public function url(): Url
    {
        return $this->url;
    }

    public function headers(): array/*Map<String, String>*/
    {
        return $this->headers;
    }

    public function body(): string
    {
        return $this->body;
    }
}