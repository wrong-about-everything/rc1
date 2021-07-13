<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\Http\Transport;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use RC\Infrastructure\Http\Request\Method\Post;
use RC\Infrastructure\Http\Request\Outbound\OutboundRequest;
use RC\Infrastructure\Http\Request\Url\FromString;
use RC\Infrastructure\Http\Response\Code\Ok;
use RC\Infrastructure\Http\Transport\WithLogging;
use RC\Infrastructure\Logging\LogId;
use RC\Infrastructure\Logging\Logs\InMemory;
use RC\Infrastructure\Uuid\FromString as UuidFromString;
use RC\Tests\Infrastructure\Http\Transport\FakeWithCodeHeadersAndBody;
use RC\Tests\Infrastructure\Http\Transport\Sleepy;

class WithLoggingTest extends TestCase
{
    public function testResponseLogged()
    {
        $logs =
            new InMemory(
                new LogId(new UuidFromString(Uuid::uuid4()->toString()))
            );

        $transport =
            new WithLogging(
                new Sleepy(
                    new FakeWithCodeHeadersAndBody(new Ok(), [], ''),
                    10
                ),
                $logs
            );

        $transport->response(new OutboundRequest(new Post(), new FromString('http://vasya.ru'), [], 'vasya'));

        $this->assertNotEmpty($logs->all());
        $this->assertCount(2, $logs->all());
    }
}
