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
use RC\Infrastructure\Http\Request\Url\Query\FromString as QueryFromString;
use RC\Infrastructure\Routing\Route;
use RC\Infrastructure\Routing\Route\RouteByMethodAndPathPattern;
use RC\Infrastructure\Routing\Route\RouteByMethodAndPathPatternWithQuery;

class RouteByMethodAndPathPatternWithQueryTest extends TestCase
{
    /**
     * @dataProvider matchingRoutesWithQuery
     */
    public function testMatchingRoutesWithQuery(Route $route, Request $request, array $parsedParamsFromRequest)
    {
        $matchResult = $route->matchResult($request);
        $this->assertTrue($matchResult->matches());
        $this->assertEquals(
            array_slice($parsedParamsFromRequest, 0, count($parsedParamsFromRequest) - 1),
            array_slice($matchResult->params(), 0, count($matchResult->params()) - 1)
        );
        $this->assertEquals(
            $parsedParamsFromRequest[count($parsedParamsFromRequest) - 1]->value(),
            $matchResult->params()[count($matchResult->params()) - 1]->value()
        );
    }

    public function matchingRoutesWithQuery(): array
    {
        return [
            [
                new RouteByMethodAndPathPatternWithQuery(new Get(), '/hello/vasya'),
                new Composite(new Get(), new FromString('https://hello.vasya.ru/hello/vasya?secret_santa=vasya&secret_vasya=junior_dos_santos'), [], ''),
                [new QueryFromString('secret_santa=vasya&secret_vasya=junior_dos_santos')]
            ],
            [
                new RouteByMethodAndPathPatternWithQuery(new Post(), 'hello/:id'),
                new Composite(new Post(), new FromString('https://hello.vasya.ru/hello/vasya?secret_santa=vasya'), [], ''),
                ['vasya', new QueryFromString('secret_santa=vasya')]
            ],
            [
                new RouteByMethodAndPathPatternWithQuery(new Put(), '/hello/vasya/:how/:are/you'),
                new Composite(new Put(), new FromString('https://hello.vasya.ru/hello/vasya/i/love/you?vasya'), [], ''),
                ['i', 'love', new QueryFromString('vasya')]
            ],
        ];
    }

    /**
     * @dataProvider matchingRoutesWithoutQuery
     */
    public function testMatchingRoutesWithoutQuery(Route $route, Request $request, array $parsedParamsFromRequest)
    {
        $matchResult = $route->matchResult($request);
        $this->assertTrue($matchResult->matches());
        $this->assertEquals(
            $parsedParamsFromRequest,
            $matchResult->params()
        );
    }

    public function matchingRoutesWithoutQuery(): array
    {
        return [
            [
                new RouteByMethodAndPathPattern(new Get(), '/hello/vasya'),
                new Composite(new Get(), new FromString('https://hello.vasya.ru/hello/vasya'), [], ''),
                []
            ],
            [
                new RouteByMethodAndPathPattern(new Post(), 'hello/:id'),
                new Composite(new Post(), new FromString('https://hello.vasya.ru/hello/vasya'), [], ''),
                ['vasya']
            ],
            [
                new RouteByMethodAndPathPattern(new Delete(), '/:help/vasya'),
                new Composite(new Delete(), new FromString('https://hello.vasya.ru/hello/vasya'), [], ''),
                ['hello']
            ],
            [
                new RouteByMethodAndPathPattern(new Put(), '/hello/vasya/:how/:are/you'),
                new Composite(new Put(), new FromString('https://hello.vasya.ru/hello/vasya/i/love/you'), [], ''),
                ['i', 'love']
            ],
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
                new RouteByMethodAndPathPatternWithQuery(new Get(), '/hello/vasya'),
                new Composite(new Post(), new FromString('https://hello.vasya.ru/hello/vasya?hey=you'), [], '')
            ],
            [
                new RouteByMethodAndPathPatternWithQuery(new Post(), '/hello/:id/vasya'),
                new Composite(new Post(), new FromString('https://hello.vasya.ru/hello/vasya?how=are&you'), [], '')
            ],
            [
                new RouteByMethodAndPathPatternWithQuery(new Delete(), '/help/vasya'),
                new Composite(new Delete(), new FromString('https://hello.vasya.ru/hello/vasya'), [], '')
            ],
            [
                new RouteByMethodAndPathPatternWithQuery(new Put(), '/hello/vasya/:how/:are/you'),
                new Composite(new Put(), new FromString('https://hello.vasya.ru/hello/vasya/love/you'), [], '')
            ],
        ];
    }
}
