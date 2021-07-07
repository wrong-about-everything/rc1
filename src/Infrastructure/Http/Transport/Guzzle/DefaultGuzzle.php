<?php

declare(strict_types=1);

namespace RC\Infrastructure\Http\Transport\Guzzle;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;

class DefaultGuzzle extends Guzzle
{
    private $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    protected function client(): ClientInterface
    {
        return $this->client;
    }

    protected function options(): array
    {
        return [
            RequestOptions::CONNECT_TIMEOUT => 5,
            RequestOptions::TIMEOUT => 5,
            RequestOptions::HTTP_ERRORS => false,
        ];
    }
}
