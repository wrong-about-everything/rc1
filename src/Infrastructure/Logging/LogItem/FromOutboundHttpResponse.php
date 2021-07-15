<?php

declare(strict_types=1);

namespace RC\Infrastructure\Logging\LogItem;

use Meringue\Timeline\Point\Now;
use RC\Infrastructure\Http\Response\Outbound\Response;
use RC\Infrastructure\Logging\LogItem;
use RC\Infrastructure\Logging\Severity\Info;

class FromOutboundHttpResponse implements LogItem
{
    private $response;

    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    public function value(): array
    {
        return [
            'timestamp' => (new Now())->value(),
            'severity' => (new Info())->value(),
            'message' => 'Outbound http response',
            'data' => [
                'headers' => $this->response->headers(),
                'body' => $this->response->body(),
                'code' => $this->response->code()->value(),
            ],
        ];
    }
}
