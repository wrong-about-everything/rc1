<?php

declare(strict_types = 1);

namespace RC\Infrastructure\Http\Transport;

use RC\Infrastructure\Http\Request\Outbound\EagerlyInvoked;
use RC\Infrastructure\Http\Request\Outbound\Request;
use RC\Infrastructure\Http\Response\Code\Ok;
use RC\Infrastructure\Http\Response\Inbound\Response;
use RC\Infrastructure\Http\Response\Inbound\DefaultResponse;
use RC\Tests\Infrastructure\Http\Transport\FakeTransport;

class Indifferent implements FakeTransport
{
    private $requests;

    public function __construct()
    {
        $this->requests = [];
    }

    public function response(Request $request): Response
    {
        $this->requests[] = new EagerlyInvoked($request);
        return new DefaultResponse(new Ok(), [], '');
    }

    /**
     * @return Request[]
     */
    public function sentRequests(): array
    {
        return $this->requests;
    }
}
