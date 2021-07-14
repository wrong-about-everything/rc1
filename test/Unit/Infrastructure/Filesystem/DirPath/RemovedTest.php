<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\Filesystem\DirPath;

use PHPUnit\Framework\TestCase;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use RC\Infrastructure\Filesystem\DirPath\Created;
use RC\Infrastructure\Filesystem\DirPath\Removed;
use RC\Infrastructure\Filesystem\DirPath\FromNestedDirectoryNames;
use RC\Infrastructure\Filesystem\Filename\PortableFromString;
use RC\Tests\Infrastructure\Environment\Reset;
use RC\Tests\Infrastructure\Filesystem\DirPath\Tmp;

class RemovedTest extends TestCase
{
    public function testWhenDirectoryDoesNotExistThenItIsNotRemoved()
    {
        $dir = new Removed(new FromNestedDirectoryNames(new Tmp(), new PortableFromString('vasya')));

        $this->assertFalse($dir->exists());
        $this->assertFalse(
            $dir->value()->isSuccessful()
        );
        $this->assertFalse(
            (new FromNestedDirectoryNames(new Tmp(), new PortableFromString('vasya')))->exists()
        );
    }

    public function testWhenDirectoryExistsThenItIsRemoved()
    {
        (new Created(
            new FromNestedDirectoryNames(
                new Created(
                    new FromNestedDirectoryNames(new Tmp(), new PortableFromString('vasya'))
                ),
                new PortableFromString('fedya')
            )
        ))
            ->value();
        $dir = new Removed(new FromNestedDirectoryNames(new Tmp(), new PortableFromString('vasya')));

        $this->assertFalse($dir->exists());
        $this->assertTrue($dir->value()->isSuccessful());
        $this->assertFalse(
            (new FromNestedDirectoryNames(new Tmp(), new PortableFromString('vasya')))->exists()
        );
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }
}