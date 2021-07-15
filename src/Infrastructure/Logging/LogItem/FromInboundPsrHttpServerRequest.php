<?php

declare(strict_types=1);

namespace RC\Infrastructure\Logging\LogItem;

use Meringue\Timeline\Point\Now;
use Psr\Http\Message\ServerRequestInterface;
use RC\Infrastructure\Logging\LogItem;
use RC\Infrastructure\Logging\Severity\Info;

class FromInboundPsrHttpServerRequest implements LogItem
{
    private $serverRequest;

    public function __construct(ServerRequestInterface $serverRequest)
    {
        $this->serverRequest = $serverRequest;
    }

    public function value(): array
    {
        return [
            'timestamp' => (new Now())->value(),
            'severity' => (new Info())->value(),
            'message' => 'Inbound http request',
            'data' => [
                'method' => $this->serverRequest->getMethod(),
                'url' => $this->serverRequest->getRequestTarget(),
                'headers' => $this->serverRequest->getHeaders(),
                'body' => $this->serverRequest->getBody()->__toString(),
            ],
        ];
    }
}
