<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\Http\Request\Url\Query;

use PHPUnit\Framework\TestCase;
use RC\Infrastructure\Http\Request\Url\FromString;
use RC\Infrastructure\Http\Request\Url\Query\FromUrl;

class FromUrlTest extends TestCase
{
    /**
     * @dataProvider specifiedQueries
     */
    public function testQuery(string $stringUri, string $parsed)
    {
        $this->assertEquals(
            $parsed,
            (new FromUrl(
                new FromString($stringUri)
            ))
                ->value()
        );
    }

    public function specifiedQueries()
    {
        return
            [
                [
                    'http://vasya?limit=10&offset=100',
                    'limit' => 'limit=10&offset=100'
                ],
                [
                    'http://vasya?form[name]=vasya&form[last_name]=belov&limit=10&offset=100',
                    'form[name]=vasya&form[last_name]=belov&limit=10&offset=100',
                ],
                [
                    'http://vasya/?q:=/sdf?sd&i[h#]',
                    'q:=/sdf?sd&i[h',
                ],
                [
                    'http://vasya/?[h=q',
                    '[h=q'
                ],
            ];
    }

    public function testNonSpecifiedQuery()
    {
        $this->assertFalse(
            (new FromUrl(
                new FromString('http://vasya')
            ))
                ->isSpecified()
        );
    }

}