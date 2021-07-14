<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\Filesystem\DirPath;

use Exception;
use PHPUnit\Framework\TestCase;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use RC\Infrastructure\Filesystem\DirPath\ExistentFromAbsolutePathString;
use RC\Infrastructure\Filesystem\DirPath\FromNestedDirectoryNames;
use RC\Infrastructure\Filesystem\DirPath\Created;
use RC\Infrastructure\Filesystem\Filename\PortableFromString;
use RC\Tests\Infrastructure\Environment\Reset;
use RC\Tests\Infrastructure\Filesystem\DirPath\Tmp;

class ExistentFromAbsolutePathStringTest extends TestCase
{
    public function testExistentDirectory()
    {
        (new Created(
            new FromNestedDirectoryNames(
                new Tmp(),
                new PortableFromString('kvass')
            )
        ))
            ->value();

        $dir =
            new ExistentFromAbsolutePathString(
                (new FromNestedDirectoryNames(
                    new Tmp(),
                    new PortableFromString('kvass')
                ))
                    ->value()->pure()->raw()
            );

        $this->assertEquals(
            (new FromNestedDirectoryNames(
                new Tmp(),
                new PortableFromString('kvass')
            ))
                ->value()->pure()->raw(),
            $dir->value()->pure()->raw()
        );
    }

    public function testNonExistentDirectory()
    {
        try {
            new ExistentFromAbsolutePathString('/tmp/kvass');
        } catch (Exception $e) {
            return $this->assertTrue(true);
        }

        $this->fail('An exception should have been thrown');
    }

    public function testInvalidPath()
    {
        try {
            new ExistentFromAbsolutePathString('s6d5s8dg#$%^&\\');
        } catch (Exception $e) {
            return $this->assertTrue(true);
        }

        $this->fail('An exception should have been thrown');
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }
}