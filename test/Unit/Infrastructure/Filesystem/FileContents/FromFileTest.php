<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\Filesystem\FileContents;

use PHPUnit\Framework\TestCase;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use RC\Infrastructure\Filesystem\FileContents\FromFilePath;
use RC\Infrastructure\Filesystem\FileContents\Emptie;
use RC\Infrastructure\Filesystem\FileContents\FromString;
use RC\Infrastructure\Filesystem\Filename\PortableFromString;
use RC\Infrastructure\Filesystem\FilePath\Created;
use RC\Infrastructure\Filesystem\FilePath\FromDirAndFileName;
use RC\Tests\Infrastructure\Environment\Reset;
use RC\Tests\Infrastructure\Filesystem\DirPath\Tmp;

class FromFileTest extends TestCase
{
    public function testWhenFilePathDoesNotExistThenContentIsNotSuccessful()
    {
        $content =
            new FromFilePath(
                new FromDirAndFileName(new Tmp(), new PortableFromString('vasya'))
            );

        $this->assertFalse($content->value()->isSuccessful());
    }

    public function testWhenFilePathExistsThenContentIsRetrieved()
    {
        (new Created(
            new FromDirAndFileName(new Tmp(), new PortableFromString('vasya')),
            new FromString('hello!')
        ))
            ->value();

        $content =
            new FromFilePath(
                new FromDirAndFileName(new Tmp(), new PortableFromString('vasya'))
            );

        $this->assertTrue($content->value()->isSuccessful());
        $this->assertEquals(
            'hello!',
            (new FromFilePath(
                new FromDirAndFileName(new Tmp(), new PortableFromString('vasya'))
            ))
                ->value()->pure()->raw()
        );
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();;
    }
}