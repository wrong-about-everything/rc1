<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\Filesystem\DirPath;

use PHPUnit\Framework\TestCase;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use RC\Infrastructure\Filesystem\DirPath\Moved;
use RC\Infrastructure\Filesystem\DirPath\Created;
use RC\Infrastructure\Filesystem\DirPath\FromNestedDirectoryNames;
use RC\Infrastructure\Filesystem\Filename\PortableFromString;
use RC\Tests\Infrastructure\Environment\Reset;
use RC\Tests\Infrastructure\Filesystem\DirPath\Tmp;

class MovedTest extends TestCase
{
    public function testWhenOriginDirectoryDoesNotExistThenItIsNotMoved()
    {
        $dir =
            new Moved(
                new FromNestedDirectoryNames(new Tmp(), new PortableFromString('vasya')),
                new FromNestedDirectoryNames(new Tmp(), new PortableFromString('fedya'))
            );

        $this->assertFalse($dir->exists());
        $this->assertFalse($dir->value()->isSuccessful());
        $this->assertFalse(
            (new FromNestedDirectoryNames(new Tmp(), new PortableFromString('fedya')))->exists()
        );
    }

    public function testWhenBothOriginAndDestinationDirectoriesExistThenOriginIsNotMoved()
    {
        (new Created(new FromNestedDirectoryNames(new Tmp(), new PortableFromString('vasya'))))->value();
        (new Created(new FromNestedDirectoryNames(new Tmp(), new PortableFromString('fedya'))))->value();

        $dir =
            new Moved(
                new FromNestedDirectoryNames(new Tmp(), new PortableFromString('vasya')),
                new FromNestedDirectoryNames(new Tmp(), new PortableFromString('fedya'))
            );

        $this->assertTrue($dir->exists());
        $this->assertFalse($dir->value()->isSuccessful());
        $this->assertTrue(
            (new FromNestedDirectoryNames(new Tmp(), new PortableFromString('fedya')))->exists()
        );
    }

    public function testWhenOriginDirectoryExistsAndDestinationDoesNotThenOriginIsMoved()
    {
        (new Created(new FromNestedDirectoryNames(new Tmp(), new PortableFromString('vasya'))))->value();

        $dir =
            new Moved(
                new FromNestedDirectoryNames(new Tmp(), new PortableFromString('vasya')),
                new FromNestedDirectoryNames(new Tmp(), new PortableFromString('fedya'))
            );

        $this->assertTrue($dir->exists());
        $this->assertTrue($dir->value()->isSuccessful());
        $this->assertTrue(
            (new FromNestedDirectoryNames(new Tmp(), new PortableFromString('fedya')))->exists()
        );
        $this->assertEquals(
            (new FromNestedDirectoryNames(new Tmp(), new PortableFromString('fedya')))->value()->pure()->raw(),
            $dir->value()->pure()->raw()
        );
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }
}