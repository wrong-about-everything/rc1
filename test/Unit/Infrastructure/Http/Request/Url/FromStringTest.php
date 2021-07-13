<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\Http\Request\Url;

use PHPUnit\Framework\TestCase;
use RC\Infrastructure\Http\Request\Url\FromString;
use Exception;

class FromStringTest extends TestCase
{
    /**
     * @dataProvider validUrls
     */
    public function testValidUrls(string $uriString)
    {
        $query = new FromString($uriString);

        $this->assertEquals($uriString, $query->value());
    }

    public function validUrls()
    {
        return
            [
                ['http://vasya/limit=10&offset=100'],
                ['http://vasya?limit=10&offset=100'],
                ['http://192.168.24.3'], // yes, this is a valid url
                ['http://vasya:8000'],
                ['http://vasya_oms:8000'],
                ['http://nginxrest:8080'],
            ];
    }

    /**
     * @dataProvider invalidUrls
     */
    public function testInvalidUris(string $uriString)
    {
        try {
            new FromString($uriString);
        } catch (Exception $e) {
            return $this->assertTrue(true);
        }

        $this->fail('Url should not have been created');
    }

    public function invalidUrls()
    {
        return
            [
                ['vasya/limit=10&offset=100'],
                ['vasya'],
                ['192.168.24.3'],
                ['htttp://vasya?limit=10&offset=100'],
                ['http://?limit=10&offset=100'],
                ['http://.com?limit=10&offset=100'],
                ['http://#anchor'],
                ['htt://vasya?limit=10&offset=100'],
                ['htt://vasya?limit=10&offset=100'],
            ];
    }
}
