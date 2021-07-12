<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\Filesystem\FilePath;

use PHPUnit\Framework\TestCase;
use RC\Infrastructure\Filesystem\FileContents\FromString;
use RC\Infrastructure\Filesystem\Filename\PortableFromString;
use RC\Infrastructure\Filesystem\FilePath\Created;
use RC\Infrastructure\Filesystem\FilePath\FromDirAndFileName;
use RC\Infrastructure\Filesystem\FilePath\Removed;
use RC\Tests\Infrastructure\Environment\Reset;
use RC\Tests\Infrastructure\Filesystem\DirPath\Tmp;

class RemovedTest extends TestCase
{
    public function testWhenFileDoesNotExistThenItIsNotRemoved()
    {
        $filePath =
            new Removed(
                new FromDirAndFileName(new Tmp(), new PortableFromString('vasya'))
            );

        $this->assertFalse($filePath->value()->isSuccessful());
        $this->assertFalse($filePath->exists());
    }

    public function testWhenFileExistsThenItIsRemoved()
    {
        (new Created(
            new FromDirAndFileName(new Tmp(), new PortableFromString('vasya')),
            new FromString('vasya')
        ))
            ->value();

        $filePath =
            new Removed(
                new FromDirAndFileName(new Tmp(), new PortableFromString('vasya'))
            );

        $this->assertTrue($filePath->value()->isSuccessful());
        $this->assertFalse($filePath->exists());
    }

    protected function setUp(): void
    {
        (new Reset())->run();
    }
}