<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\Filesystem\DirPath;

use PHPUnit\Framework\TestCase;
use RC\Infrastructure\Filesystem\DirPath\FromNestedDirectoryNames;
use RC\Infrastructure\Filesystem\DirPath\Created;
use RC\Infrastructure\Filesystem\Filename\PortableFromString;
use RC\Tests\Infrastructure\Environment\Reset;
use RC\Tests\Infrastructure\Filesystem\DirPath\Tmp;

class FromNestedDirectoryNamesTest extends TestCase
{
    public function testExistentDirectory()
    {
        (new Created(
            new FromNestedDirectoryNames(
                new Tmp(),
                new PortableFromString('kvass')
            )
        ))
            ->value();

        $dir =
            new FromNestedDirectoryNames(
                new Tmp(),
                new PortableFromString('kvass')
            );

        $this->assertEquals(
            (new FromNestedDirectoryNames(
                new Tmp(),
                new PortableFromString('kvass')
            ))
                ->value()->pure()->raw(),
            $dir->value()->pure()->raw()
        );
    }

    public function testNonExistentDirectory()
    {
        $dir =
            new FromNestedDirectoryNames(
                new Tmp(),
                new PortableFromString('kvass')
            );

        $this->assertEquals('/tmp/project_custom_temp_dir/kvass', $dir->value()->pure()->raw());
        $this->assertFalse($dir->exists());
    }

    protected function setUp(): void
    {
        (new Reset())->run();
    }
}