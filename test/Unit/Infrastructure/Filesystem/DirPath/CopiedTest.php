<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\Filesystem\DirPath;

use PHPUnit\Framework\TestCase;
use RC\Infrastructure\Filesystem\DirPath\Copied;
use RC\Infrastructure\Filesystem\DirPath\Created;
use RC\Infrastructure\Filesystem\DirPath\FromNestedDirectoryNames;
use RC\Infrastructure\Filesystem\Filename\PortableFromString;
use RC\Tests\Infrastructure\Environment\Reset;
use RC\Tests\Infrastructure\Filesystem\DirPath\Tmp;

class CopiedTest extends TestCase
{
    public function testWhenOriginDirectoryDoesNotExistThenItIsNotCopied()
    {
        $dir =
            new Copied(
                new FromNestedDirectoryNames(new Tmp(), new PortableFromString('vasya')),
                new FromNestedDirectoryNames(new Tmp(), new PortableFromString('fedya'))
            );

        $this->assertFalse($dir->exists());
        $this->assertFalse($dir->value()->isSuccessful());
    }

    public function testWhenBothOriginAndDestinationDirectoriesExistThenOriginIsNotCopied()
    {
        (new Created(new FromNestedDirectoryNames(new Tmp(), new PortableFromString('vasya'))))->value();
        (new Created(new FromNestedDirectoryNames(new Tmp(), new PortableFromString('fedya'))))->value();

        $dir =
            new Copied(
                new FromNestedDirectoryNames(new Tmp(), new PortableFromString('vasya')),
                new FromNestedDirectoryNames(new Tmp(), new PortableFromString('fedya'))
            );

        $this->assertTrue($dir->exists());
        $this->assertFalse($dir->value()->isSuccessful());
    }

    public function testWhenOriginDirectoryExistsAndDestinationDoesNotThenOriginIsCopied()
    {
        $this->markTestIncomplete('Implement corresponding logic');
    }

    protected function setUp(): void
    {
        (new Reset())->run();
    }
}