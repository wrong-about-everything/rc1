<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\Filesystem\Filename;

use Exception;
use PHPUnit\Framework\TestCase;
use RC\Infrastructure\Filesystem\Filename\PortableFromString;

class PortableFromStringTest extends TestCase
{
    /**
     * @dataProvider portablePosixFilenames
     */
    public function testPortableFilenameCreated(string $filename)
    {
        $this->assertEquals(
            $filename,
            (new PortableFromString($filename))->value()
        );
    }

    public function portablePosixFilenames()
    {
        return [
            ['vasya'],
            ['vAsYa'],
            ['v.A.s.Ya_-__--1988'],
        ];
    }

    /**
     * @dataProvider notPortablePosixFilenames
     */
    public function testNotPortableFilenameIsNotCreated(string $filename)
    {
        try {
            new PortableFromString($filename);
        } catch (Exception $e) {
            return $this->assertTrue(true);
        }

        $this->fail(sprintf('%s is not portable and an object should not have been created', $filename));
    }

    public function notPortablePosixFilenames()
    {
        return [
            [''],
            ['@#$%'],
            ['vA*sYa'],
            [' '],
        ];
    }
}