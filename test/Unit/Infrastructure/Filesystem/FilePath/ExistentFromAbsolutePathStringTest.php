<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\Filesystem\FilePath;

use Exception;
use PHPUnit\Framework\TestCase;
use RC\Infrastructure\Filesystem\DirPath\Created;
use RC\Infrastructure\Filesystem\DirPath\FromNestedDirectoryNames;
use RC\Infrastructure\Filesystem\FileContents\Emptie;
use RC\Infrastructure\Filesystem\Filename\PortableFromString;
use RC\Infrastructure\Filesystem\FilePath\Created as CreatedFile;
use RC\Infrastructure\Filesystem\FilePath\ExistentFromAbsolutePathString;
use RC\Infrastructure\Filesystem\FilePath\FromDirAndFileName;
use RC\Tests\Infrastructure\Environment\Reset;
use RC\Tests\Infrastructure\Filesystem\DirPath\Tmp;

class ExistentFromAbsolutePathStringTest extends TestCase
{
    public function testWhenPathStringIsInvalidThenExceptionIsThrown()
    {
        try {
            new ExistentFromAbsolutePathString('36&%^*');
        } catch (Exception $e) {
            return $this->assertTrue(true);
        }

        $this->fail('An exception should have been thrown');
    }

    public function testWhenFileDoesNotExistThenExceptionIsThrown()
    {
        try {
            new ExistentFromAbsolutePathString('/sdfg/fgj/qwer/fgh/fgh/w/er/rty');
        } catch (Exception $e) {
            return $this->assertTrue(true);
        }

        $this->fail('An exception should have been thrown');
    }

    public function testWhenThisPathRepresentsADirectoryThenExceptionIsThrown()
    {
        (new Created(new FromNestedDirectoryNames(new Tmp(), new PortableFromString('vasya'))))->value();

        try {
            new ExistentFromAbsolutePathString(
                (new FromNestedDirectoryNames(new Tmp(), new PortableFromString('vasya')))
                    ->value()->pure()->raw()
            );
        } catch (Exception $e) {
            return $this->assertTrue(true);
        }

        $this->fail('An exception should have been thrown');
    }

    public function testWhenFileExistsThenEverythingIsOK()
    {
        (new CreatedFile(
            new FromDirAndFileName(new Tmp(), new PortableFromString('vasya')),
            new Emptie()
        ))
            ->value();

        $file =
            new ExistentFromAbsolutePathString(
                (new FromDirAndFileName(new Tmp(), new PortableFromString('vasya')))
                    ->value()->pure()->raw()
            );

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