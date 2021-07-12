<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\Filesystem\FilePath;

use PHPUnit\Framework\TestCase;
use RC\Infrastructure\Filesystem\DirPath\Created;
use RC\Infrastructure\Filesystem\DirPath\FromNestedDirectoryNames;
use RC\Infrastructure\Filesystem\FileContents\Emptie;
use RC\Infrastructure\Filesystem\Filename\PortableFromString;
use RC\Infrastructure\Filesystem\FilePath\Created as CreatedFile;
use RC\Infrastructure\Filesystem\FilePath\ExistentFromDirAndFileName;
use RC\Infrastructure\Filesystem\FilePath\FromDirAndFileName;
use RC\Tests\Infrastructure\Environment\Reset;
use RC\Tests\Infrastructure\Filesystem\DirPath\NonExistent;
use RC\Tests\Infrastructure\Filesystem\DirPath\Tmp;

class ExistentFromDirAndFileNameTest extends TestCase
{
    public function testDirDoesNotExist()
    {
        $filePath = new ExistentFromDirAndFileName(new NonExistent(), new PortableFromString('vasya'));

        $this->assertFalse($filePath->exists());
        $this->assertFalse($filePath->value()->isSuccessful());
    }

    public function testDirExistsButFilenameInItDoesNot()
    {
        $filePath = new ExistentFromDirAndFileName(new Tmp(), new PortableFromString('vasya'));

        $this->assertFalse($filePath->exists());
        $this->assertFalse($filePath->value()->isSuccessful());
    }

    public function testPathExistsButItIsADirectory()
    {
        (new Created(new FromNestedDirectoryNames(new Tmp(), new PortableFromString('vasya'))))->value();

        $filePath = new ExistentFromDirAndFileName(new Tmp(), new PortableFromString('vasya'));

        $this->assertFalse($filePath->exists());
        $this->assertFalse($filePath->value()->isSuccessful());

    }

    public function testWhenFileExistsThenEverythingIsOK()
    {
        (new CreatedFile(
            new FromDirAndFileName(new Tmp(), new PortableFromString('vasya')),
            new Emptie()
        ))
            ->value();

        $file = new ExistentFromDirAndFileName(new Tmp(), new PortableFromString('vasya'));

        $this->assertTrue($file->exists());
        $this->assertEquals(
            (new FromDirAndFileName(new Tmp(), new PortableFromString('vasya')))
                ->value()->pure()->raw(),
            $file->value()->pure()->raw()
        );
    }

    protected function setUp(): void
    {
        (new Reset())->run();
    }
}