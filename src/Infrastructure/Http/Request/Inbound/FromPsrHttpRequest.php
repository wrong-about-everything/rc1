<?php

declare(strict_types=1);

namespace RC\Infrastructure\Http\Request\Inbound;

use Psr\Http\Message\ServerRequestInterface;
use RC\Infrastructure\Http\Request\Inbound\Composite as CompositeRequest;
use RC\Infrastructure\Http\Request\Method;
use RC\Infrastructure\Http\Request\Method\FromString as HttpMethodFromString;
use RC\Infrastructure\Http\Request\Url;
use RC\Infrastructure\Http\Request\Url\Composite as CompositeUrl;
use RC\Infrastructure\Http\Request\Url\Fragment\NonSpecified as NonSpecifiedFragment;
use RC\Infrastructure\Http\Request\Url\Host\Localhost;
use RC\Infrastructure\Http\Request\Url\Path\FromString as PathFromString;
use RC\Infrastructure\Http\Request\Url\Port\NonSpecified as NonSpecifiedPort;
use RC\Infrastructure\Http\Request\Url\Query\FromArray as QueryFromArray;
use RC\Infrastructure\Http\Request\Url\Scheme\Https;

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
                new CompositeUrl(
                    new Https(),
                    new Localhost(),
                    new NonSpecifiedPort(),
                    new PathFromString($request->getQueryParams()['ad_hoc_path'] ?? ''),
                    new QueryFromArray($request->getQueryParams()),
                    new NonSpecifiedFragment()
                ),
                $request->getHeaders(),
                $request->getBody()->getContents()
            );
    }
}