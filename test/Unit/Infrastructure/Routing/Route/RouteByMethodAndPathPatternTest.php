<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\Routing\Route;

use PHPUnit\Framework\TestCase;
use RC\Infrastructure\Http\Request\Inbound\Composite;
use RC\Infrastructure\Http\Request\Inbound\Request;
use RC\Infrastructure\Http\Request\Method\Delete;
use RC\Infrastructure\Http\Request\Method\Get;
use RC\Infrastructure\Http\Request\Method\Post;
use RC\Infrastructure\Http\Request\Method\Put;
use RC\Infrastructure\Http\Request\Url\FromString;
use RC\Infrastructure\Routing\Route;
use RC\Infrastructure\Routing\Route\RouteByMethodAndPathPattern;

class RouteByMethodAndPathPatternTest extends TestCase
{
    /**
     * @dataProvider matchingRoutes
     */
    public function testMatchingRoutes(Route $route, Request $request, array $parsedParamsFromRequest)
    {
        $matchResult = $route->matchResult($request);
        $this->assertTrue($matchResult->matches());
        $this->assertEquals(
            $parsedParamsFromRequest,
            $matchResult->params()
        );
    }

    public function matchingRoutes(): array
    {
        return [
//            [
//                new RouteByMethodAndPathPattern(new Get(), '/hello/vasya'),
//                new Composite(new Get(), new FromString('https://hello.vasya.ru/hello/vasya'), [], ''),
//                []
//            ],
            [
                new RouteByMethodAndPathPattern(new Post(), 'hello/:id'),
                new Composite(new Post(), new FromString('https://hello.vasya.ru/hello/vasya'), [], ''),
                ['vasya']
            ],
//            [
//                new RouteByMethodAndPathPattern(new Delete(), '/:help/vasya'),
//                new Composite(new Delete(), new FromString('https://hello.vasya.ru/hello/vasya'), [], ''),
//                ['hello']
//            ],
//            [
//                new RouteByMethodAndPathPattern(new Put(), '/hello/vasya/:how/:are/you'),
//                new Composite(new Put(), new FromString('https://hello.vasya.ru/hello/vasya/i/love/you'), [], ''),
//                ['i', 'love']
//            ],
        ];
    }

    /**
     * @dataProvider nonMatchingRoutes
     */
    public function testNonMatchingRoutes(Route $route, Request $request)
    {
        $matchResult = $route->matchResult($request);
        $this->assertFalse($matchResult->matches());
    }

    public function nonMatchingRoutes(): array
    {
        return [
            [
                new RouteByMethodAndPathPattern(new Get(), '/hello/vasya'),
                new Composite(new Post(), new FromString('https://hello.vasya.ru/hello/vasya'), [], '')
            ],
            [
                new RouteByMethodAndPathPattern(new Post(), '/hello/:id/vasya'),
                new Composite(new Post(), new FromString('https://hello.vasya.ru/hello/vasya'), [], '')
            ],
            [
                new RouteByMethodAndPathPattern(new Delete(), '/help/vasya'),
                new Composite(new Delete(), new FromString('https://hello.vasya.ru/hello/vasya'), [], '')
            ],
            [
                new RouteByMethodAndPathPattern(new Put(), '/hello/vasya/:how/:are/you'),
                new Composite(new Put(), new FromString('https://hello.vasya.ru/hello/vasya/love/you'), [], '')
            ],
        ];
    }
}
