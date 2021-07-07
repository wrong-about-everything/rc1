<?php

declare(strict_types=1);

namespace RC\Infrastructure\Http\Transport\Guzzle;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;

class WithProxy extends Guzzle
{
    private $guzzle;
    private $proxy;

    public function __construct(Guzzle $guzzle, string $proxy)
    {
        $this->guzzle = $guzzle;
        $this->proxy = $proxy;
    }

    protected function client(): ClientInterface
    {
        return $this->guzzle->client();
    }

    protected function options(): array
    {
        return
            [RequestOptions::PROXY => $this->proxy]
            +
            $this->guzzle->options();
    }
}