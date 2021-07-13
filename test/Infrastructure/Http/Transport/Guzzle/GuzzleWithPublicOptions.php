<?php

declare(strict_types=1);

namespace RC\Tests\Infrastructure\Http\Transport\Guzzle;

use GuzzleHttp\ClientInterface;
use RC\Infrastructure\Http\Transport\Guzzle\Guzzle;

class GuzzleWithPublicOptions extends Guzzle
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

    public function options(): array
    {
        return $this->guzzle->options();
    }
}