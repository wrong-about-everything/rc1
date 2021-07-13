<?php

declare(strict_types=1);

namespace RC\Tests\Infrastructure\Http\Transport\Guzzle;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ClientStub implements ClientInterface
{
    private $response;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function send(RequestInterface $request, array $options = []): ResponseInterface
    {
        return $this->response;
    }

    public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface
    {
        return new FulfilledPromise($this->response);
    }

    public function request(string $method, $uri, array $options = []): ResponseInterface
    {
        return $this->response;
    }

    public function requestAsync(string $method, $uri, array $options = []): PromiseInterface
    {
        return new FulfilledPromise($this->response);
    }

    public function getConfig(?string $option = null)
    {
        return $this->response;
    }
}