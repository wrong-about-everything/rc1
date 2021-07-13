<?php

declare(strict_types=1);

namespace RC\Tests\Infrastructure\Http\Transport;

use Closure;
use RC\Infrastructure\Http\Request\Outbound\Request;
use RC\Infrastructure\Http\Response\Inbound\Response;

class FakeWithClosures implements FakeTransport
{
    private $response;
    private $urlClosure;
    private $bodyClosure;
    private $requests;

    public function __construct(Response $response, Closure $urlClosure, Closure $bodyClosure)
    {
        $this->response = $response;
        $this->urlClosure = $urlClosure;
        $this->bodyClosure = $bodyClosure;
        $this->requests = [];
    }

    public function response(Request $request): Response
    {
        $this->requests[] = $request;

        ($this->urlClosure)($request->url());
        ($this->bodyClosure)($request->body());

        return $this->response;
    }

    public function sentRequests(): array
    {
        return $this->requests;
    }
}
