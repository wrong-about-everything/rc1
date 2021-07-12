<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\Filesystem\FileContents;

use PHPUnit\Framework\TestCase;
use RC\Infrastructure\Filesystem\FileContents\AppendedConcurrentSafelyToExistingFile;
use RC\Infrastructure\Filesystem\FileContents\Emptie;
use RC\Infrastructure\Filesystem\FileContents\FromFilePath;
use RC\Infrastructure\Filesystem\Filename\PortableFromString;
use RC\Infrastructure\Filesystem\FilePath\Created;
use RC\Infrastructure\Filesystem\FilePath\FromDirAndFileName;
use RC\Tests\Infrastructure\Environment\Reset;
use RC\Tests\Infrastructure\Filesystem\DirPath\Tmp;

class AppendedConcurrentSafelyToExistingFileTest extends TestCase
{
    public function testWhenFilePathDoesNotExistThenNewContentIsNotAppended()
    {
        $content =
            new AppendedConcurrentSafelyToExistingFile(
                new FromDirAndFileName(new Tmp(), new PortableFromString('vasya')),
                'hello!'
            );

        $this->assertFalse($content->value()->isSuccessful());
    }

    public function testWhenFilePathExistsThenNewContentIsAppended()
    {
        (new Created(
            new FromDirAndFileName(new Tmp(), new PortableFromString('vasya')),
            new Emptie()
        ))
            ->value();

        $content =
            new AppendedConcurrentSafelyToExistingFile(
                new FromDirAndFileName(new Tmp(), new PortableFromString('vasya')),
                'hello!'
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
        (new Reset())->run();;
    }
}