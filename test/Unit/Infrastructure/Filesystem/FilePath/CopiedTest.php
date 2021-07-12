<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\Filesystem\FilePath;

use PHPUnit\Framework\TestCase;
use RC\Infrastructure\Filesystem\FileContents\Emptie;
use RC\Infrastructure\Filesystem\FileContents\FromFilePath;
use RC\Infrastructure\Filesystem\FileContents\FromString;
use RC\Infrastructure\Filesystem\FilePath\Copied;
use RC\Infrastructure\Filesystem\FilePath\Created;
use RC\Infrastructure\Filesystem\FilePath\FromDirAndFileName;
use RC\Infrastructure\Filesystem\Filename\PortableFromString;
use RC\Tests\Infrastructure\Environment\Reset;
use RC\Tests\Infrastructure\Filesystem\DirPath\Tmp;

class CopiedTest extends TestCase
{
    public function testWhenOriginFileDoesNotExistThenItIsNotCopied()
    {
        $dir =
            new Copied(
                new FromDirAndFileName(new Tmp(), new PortableFromString('vasya')),
                new FromDirAndFileName(new Tmp(), new PortableFromString('fedya'))
            );

        $this->assertFalse($dir->exists());
        $this->assertFalse($dir->value()->isSuccessful());
    }

    public function testWhenBothOriginAndDestinationFilesExistThenOriginIsNotCopied()
    {
        (new Created(
            new FromDirAndFileName(new Tmp(), new PortableFromString('vasya')),
            new Emptie()
        ))
            ->value();
        (new Created(
            new FromDirAndFileName(new Tmp(), new PortableFromString('fedya')),
            new Emptie()
        ))
            ->value();

        $dir =
            new Copied(
                new FromDirAndFileName(new Tmp(), new PortableFromString('vasya')),
                new FromDirAndFileName(new Tmp(), new PortableFromString('fedya'))
            );

        $this->assertTrue($dir->exists());
        $this->assertFalse($dir->value()->isSuccessful());
    }

    public function testWhenOriginFileExistsAndDestinationDoesNotThenOriginIsCopied()
    {
        (new Created(
            new FromDirAndFileName(new Tmp(), new PortableFromString('vasya')),
            new FromString('Hey!')
        ))
            ->value();

        $dir =
            new Copied(
                new FromDirAndFileName(new Tmp(), new PortableFromString('vasya')),
                new FromDirAndFileName(new Tmp(), new PortableFromString('fedya'))
            );

        $this->assertTrue($dir->exists());
        $this->assertTrue($dir->value()->isSuccessful());
        $this->assertEquals(
            'Hey!',
            (new FromFilePath(
                new FromDirAndFileName(new Tmp(), new PortableFromString('fedya'))
            ))
                ->value()->pure()->raw()
        );
    }

    protected function setUp(): void
    {
        (new Reset())->run();
    }
}