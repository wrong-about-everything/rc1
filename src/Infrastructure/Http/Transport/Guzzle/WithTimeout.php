<?php

declare(strict_types=1);

namespace RC\Infrastructure\Http\Transport\Guzzle;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Exception;

class WithTimeout extends Guzzle
{
    private $guzzle;
    private $timeout;

    /**
     * @param Guzzle $guzzle
     * @param int $timeout In seconds
     */
    public function __construct(Guzzle $guzzle, int $timeout, bool $iRealizeThatHighTimeoutCanPutTheSystemDown = false)
    {
        if (!$iRealizeThatHighTimeoutCanPutTheSystemDown && $timeout > 30) {
            throw new Exception(
                'Increasing timeout THAT much will cause the whole system to run out of workers way too soon.'
                    . ' If you are sure you do fully realize the consequences, set the third argument to true -- and let God be with you.'
            );
        }

        $this->guzzle = $guzzle;
        $this->timeout = $timeout;
    }

    protected function client(): ClientInterface
    {
        return $this->guzzle->client();
    }

    protected function options(): array
    {
        return
            [RequestOptions::TIMEOUT => $this->timeout]
                +
            $this->guzzle->options();
    }
}
