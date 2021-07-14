<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\Filesystem\FilePath;

use PHPUnit\Framework\TestCase;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use RC\Infrastructure\Filesystem\DirPath\Created;
use RC\Infrastructure\Filesystem\DirPath\FromNestedDirectoryNames;
use RC\Infrastructure\Filesystem\FileContents\Emptie;
use RC\Infrastructure\Filesystem\Filename\PortableFromString;
use RC\Infrastructure\Filesystem\FilePath\Created as CreatedFile;
use RC\Infrastructure\Filesystem\FilePath\FromDirAndFileName;
use RC\Tests\Infrastructure\Environment\Reset;
use RC\Tests\Infrastructure\Filesystem\DirPath\NonExistent;
use RC\Tests\Infrastructure\Filesystem\DirPath\Tmp;

class FromDirAndFileNameTest extends TestCase
{
    public function testDirDoesNotExist()
    {
        $filePath = new FromDirAndFileName(new NonExistent(), new PortableFromString('vasya'));

        $this->assertFalse($filePath->exists());
        $this->assertTrue($filePath->value()->isSuccessful());
        $this->assertEquals(
            sprintf('%s/vasya', (new NonExistent())->value()->pure()->raw()),
            $filePath->value()->pure()->raw()
        );
    }

    public function testDirExistsButFilenameInItDoesNot()
    {
        $filePath = new FromDirAndFileName(new Tmp(), new PortableFromString('vasya'));

        $this->assertFalse($filePath->exists());
        $this->assertTrue($filePath->value()->isSuccessful());
        $this->assertEquals(
            sprintf('%s/vasya', (new Tmp())->value()->pure()->raw()),
            $filePath->value()->pure()->raw()
        );
    }

    public function testPathExistsButItIsADirectory()
    {
        (new Created(new FromNestedDirectoryNames(new Tmp(), new PortableFromString('vasya'))))->value();

        $filePath = new FromDirAndFileName(new Tmp(), new PortableFromString('vasya'));

        $this->assertFalse($filePath->exists());
        $this->assertTrue($filePath->value()->isSuccessful());
        $this->assertEquals(
            sprintf('%s/vasya', (new Tmp())->value()->pure()->raw()),
            $filePath->value()->pure()->raw()
        );
    }

    public function testWhenFileExistsThenEverythingIsOK()
    {
        (new CreatedFile(
            new FromDirAndFileName(new Tmp(), new PortableFromString('vasya')),
            new Emptie()
        ))
            ->value();

        $filePath = new FromDirAndFileName(new Tmp(), new PortableFromString('vasya'));

        $this->assertTrue($filePath->exists());
        $this->assertTrue($filePath->value()->isSuccessful());
        $this->assertEquals(
            sprintf('%s/vasya', (new Tmp())->value()->pure()->raw()),
            $filePath->value()->pure()->raw()
        );
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }
}