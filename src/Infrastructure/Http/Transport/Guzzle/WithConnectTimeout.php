<?php

declare(strict_types=1);

namespace RC\Infrastructure\Http\Transport\Guzzle;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Exception;

class WithConnectTimeout extends Guzzle
{
    private $guzzle;
    private $connectTimeout;

    /**
     * @param Guzzle $guzzle
     * @param int $connectTimeout In seconds
     */
    public function __construct(Guzzle $guzzle, int $connectTimeout, bool $iRealizeThatHighTimeoutWillPutTheSystemDown = false)
    {
        if (!$iRealizeThatHighTimeoutWillPutTheSystemDown && $connectTimeout > 30) {
            throw new Exception(
                'Increasing timeout THAT much will cause the whole system to run out of workers way too soon.'
                    . ' If you are sure you do fully realize the consequences, set the third argument to true.'
            );
        }

        $this->guzzle = $guzzle;
        $this->connectTimeout = $connectTimeout;
    }

    protected function client(): ClientInterface
    {
        return $this->guzzle->client();
    }

    protected function options(): array
    {
        return
            [RequestOptions::CONNECT_TIMEOUT => $this->connectTimeout]
                +
            $this->guzzle->options();
    }
}