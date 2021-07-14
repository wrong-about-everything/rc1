<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\Filesystem\FilePath;

use PHPUnit\Framework\TestCase;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use RC\Infrastructure\Filesystem\FileContents\FromString;
use RC\Infrastructure\Filesystem\Filename\PortableFromString;
use RC\Infrastructure\Filesystem\FilePath\Created;
use RC\Infrastructure\Filesystem\FilePath\FromDirAndFileName;
use RC\Tests\Infrastructure\Environment\Reset;
use RC\Tests\Infrastructure\Filesystem\DirPath\Tmp;

class CreatedTest extends TestCase
{
    public function testWhenFileDoesNotExistThenItIsCreated()
    {
        $filePath =
            new Created(
                new FromDirAndFileName(new Tmp(), new PortableFromString('vasya')),
                new FromString('vasya')
            );

        $this->assertTrue($filePath->value()->isSuccessful());
        $this->assertTrue($filePath->exists());
    }

    public function testWhenFileExistsThenItIsNotCreated()
    {
        (new Created(
            new FromDirAndFileName(new Tmp(), new PortableFromString('vasya')),
            new FromString('vasya')
        ))
            ->value();

        $filePath =
            new Created(
                new FromDirAndFileName(new Tmp(), new PortableFromString('vasya')),
                new FromString('vasya')
            );

        $this->assertFalse($filePath->value()->isSuccessful());
        $this->assertTrue($filePath->exists());
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }
}