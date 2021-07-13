<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\Http\Transport\Guzzle;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\TestCase;
use RC\Infrastructure\Http\Request\Method\Post;
use RC\Infrastructure\Http\Request\Outbound\OutboundRequest;
use RC\Infrastructure\Http\Request\Url\FromString;
use RC\Infrastructure\Http\Transport\Guzzle\DefaultGuzzle;
use RC\Infrastructure\Http\Transport\Guzzle\WithTimeout;
use RC\Tests\Infrastructure\Http\Transport\Guzzle\ClientStub;
use RC\Tests\Infrastructure\Http\Transport\Guzzle\GuzzleWithPublicOptions;

class WithTimeoutTest extends TestCase
{
    public function testSuccess()
    {
        $guzzle =
            new GuzzleWithPublicOptions(
                new WithTimeout(
                    new DefaultGuzzle(
                        new ClientStub(new Response())
                    ),
                    25
                )
            );

        $response =
            $guzzle
                ->response(
                    new OutboundRequest(
                        new Post(),
                        new FromString('http://vasya.ru'),
                        [],
                        ''
                    )
                )
        ;

        $this->assertTrue($response->isAvailable());
        $this->assertEquals(200, $response->code()->value());
        $this->assertEquals('', $response->body());
        $this->assertEquals([], $response->headers());
        $this->assertEquals(25, $guzzle->options()[RequestOptions::TIMEOUT]);
    }
}