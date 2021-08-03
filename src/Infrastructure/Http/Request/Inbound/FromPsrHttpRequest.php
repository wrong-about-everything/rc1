<?php

declare(strict_types=1);

namespace RC\Infrastructure\Http\Request\Inbound;

use Psr\Http\Message\ServerRequestInterface;
use RC\Infrastructure\Http\Request\Inbound\Composite as CompositeRequest;
use RC\Infrastructure\Http\Request\Method;
use RC\Infrastructure\Http\Request\Method\FromString as HttpMethodFromString;
use RC\Infrastructure\Http\Request\Url;
use RC\Infrastructure\Http\Request\Url\FromString;

class FromPsrHttpRequest implements Request
{
    private $concrete;

    public function __construct(ServerRequestInterface $psrRequest)
    {
        $this->concrete = $this->concrete($psrRequest);
    }

    public function method(): Method
    {
        return $this->concrete->method();
    }

    public function url(): Url
    {
        return $this->concrete->url();
    }

    public function headers(): array/*Map<String, String>*/
    {
        return $this->concrete->headers();
    }

    public function body(): string
    {
        return $this->concrete->body();
    }

    private function concrete(ServerRequestInterface $request): CompositeRequest
    {
        return
            new CompositeRequest(
                new HttpMethodFromString($request->getMethod()),
                new FromString($request->getUri()->__toString()),
                $request->getHeaders(),
                $request->getBody()->__toString()
            );
    }
}