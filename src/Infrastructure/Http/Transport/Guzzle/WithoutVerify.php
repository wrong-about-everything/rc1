<?php

declare(strict_types=1);

namespace RC\Infrastructure\Http\Transport\Guzzle;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;

class WithoutVerify extends Guzzle
{
    private $guzzle;

    public function __construct(Guzzle $guzzle)
    {
        $this->guzzle = $guzzle;
    }

    protected function client(): ClientInterface
    {
        return $this->guzzle->client();
    }

    protected function options(): array
    {
        return
            [RequestOptions::VERIFY => false]
            +
            $this->guzzle->options();
    }
}
