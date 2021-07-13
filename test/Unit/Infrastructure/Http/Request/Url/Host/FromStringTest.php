<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\Http\Request\Url\Host;

use Exception;
use PHPUnit\Framework\TestCase;
use RC\Infrastructure\Http\Request\Url\Host\FromString;

class FromStringTest extends TestCase
{
    /**
     * @dataProvider validHosts
     */
    public function testValidHosts(string $inputString, string $host)
    {
        $this->assertEquals(
            $host,
            (new FromString($inputString))->value()
        );
    }

    public function validHosts()
    {
        return [
            ['localhost', 'localhost/'],
            ['vasya', 'vasya/'],
            ['vasya/', 'vasya/'],
            ['192.168.19.214', '192.168.19.214/'],
            ['198.125.127.521', '198.125.127.521/'],
            ['200d5f41:0654dfg654gdb8.85a3:0000.0000:8a2e.03sdgf8g798we897y70:7334', '200d5f41:0654dfg654gdb8.85a3:0000.0000:8a2e.03sdgf8g798we897y70:7334/'],
        ];
    }

    /**
     * @dataProvider invalidHosts
     */
    public function testInvalidHosts(string $inputString)
    {
        try {
            new FromString($inputString);
        } catch (Exception $e) {
            return $this->assertTrue(true);
        }

        $this->fail('An exception should have been thrown');
    }

    public function invalidHosts()
    {
        return [
            ['##'],
            ['?'],
            ['/////'],
            ['foo.bar?q=Spaces should be encoded'],
            [' vasya belov must live'],
            ["漢 字"],
        ];
    }

}
