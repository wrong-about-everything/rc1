<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\Http\Request\Inbound;

use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use RC\Infrastructure\Http\Request\Inbound\FromPsrHttpRequest;
use RC\Infrastructure\Http\Request\Method\Post;

class FromPsrHttpRequestTest extends TestCase
{
    public function test()
    {
        $request =
            new FromPsrHttpRequest(
                new ServerRequest(
                    'post',
                    'https://example.morg/register?vasya=krasavchick#benefits',
                    [],
                    'so good!'
                )
            );

        $this->assertEquals('https://example.morg/register?vasya=krasavchick#benefits', $request->url()->value());
        $this->assertEquals((new Post())->value(), $request->method()->value());
        $this->assertEquals('so good!', $request->body());
    }
}