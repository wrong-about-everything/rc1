<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\Http\Request\Url\Host;

use PHPUnit\Framework\TestCase;
use RC\Infrastructure\Http\Request\Url\FromString as UrlFromString;
use RC\Infrastructure\Http\Request\Url\Host\FromUrl as HostFromUrl;

class FromUrlTest extends TestCase
{
    /**
     * @dataProvider urlAndHostPairs
     */
    public function testSuccess(string $url, string $host)
    {
        $this->assertEquals(
            $host,
            (new HostFromUrl(new UrlFromString($url)))
                ->value()
        );
    }

    public function urlAndHostPairs()
    {
        return [
            ['http://subdomain.vasya.ru/belov', 'subdomain.vasya.ru'],
            ['http://lowcahlhost', 'lowcahlhost'],
            ['http://654.321.9879.5646', '654.321.9879.5646'],
        ];
    }
}
