<?php

declare(strict_types = 1);

namespace RC\Tests\Infrastructure\Http\Transport;

use RC\Infrastructure\Http\Request\Outbound\Request;
use RC\Infrastructure\Http\Response\Inbound\Response;

class FakeWithResponse implements FakeTransport
{
    private $response;
    private $requests;

    public function __construct(Response $response)
    {
        $this->response = $response;
        $this->requests = [];
    }

    public function response(Request $request): Response
    {
        $this->requests[] = $request;
        return $this->response;
    }

    public function sentRequests(): array
    {
        return $this->requests;
    }
}
