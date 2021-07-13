<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\Http\Request\Url\Query;

use PHPUnit\Framework\TestCase;
use RC\Infrastructure\Http\Request\Url\Query\FromString;

class FromStringTest extends TestCase
{
    /**
     * @dataProvider queries
     */
    public function testQuery(string $queryString, string $expected)
    {
        $query = new FromString($queryString);

        $this->assertEquals($expected, $query->value());
    }

    public function queries()
    {
        return
            [
                [
                    'limit=10&offset=100',
                    'limit=10&offset=100',
                ],
                [
                    'q:=/sdf?#sdi[&h]',
                    'q:=/sdf?#sdi[&h]',
                ],
                [
                    '',
                    '',
                ],
            ];
    }
}
