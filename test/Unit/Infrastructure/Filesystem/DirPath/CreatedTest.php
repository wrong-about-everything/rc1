<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\Filesystem\DirPath;

use PHPUnit\Framework\TestCase;
use RC\Infrastructure\Filesystem\DirPath\Created;
use RC\Infrastructure\Filesystem\DirPath\FromNestedDirectoryNames;
use RC\Infrastructure\Filesystem\Filename\PortableFromString;
use RC\Tests\Infrastructure\Environment\Reset;
use RC\Tests\Infrastructure\Filesystem\DirPath\Tmp;

class CreatedTest extends TestCase
{
    public function testWhenDirectoryDoesNotExistThenItIsCreated()
    {
        $dir = new Created(new FromNestedDirectoryNames(new Tmp(), new PortableFromString('vasya')));

        $this->assertTrue($dir->exists());
        $this->assertEquals(
            (new FromNestedDirectoryNames(new Tmp(), new PortableFromString('vasya')))->value()->pure()->raw(),
            $dir->value()->pure()->raw()
        );
        $this->assertTrue(
            (new FromNestedDirectoryNames(new Tmp(), new PortableFromString('vasya')))->exists()
        );
    }

    public function testWhenDirectoryExistsThenItIsNotCreated()
    {
        (new Created(new FromNestedDirectoryNames(new Tmp(), new PortableFromString('vasya'))))->value();
        $dir = new Created(new FromNestedDirectoryNames(new Tmp(), new PortableFromString('vasya')));

        $this->assertTrue($dir->exists());
        $this->assertFalse($dir->value()->isSuccessful());
        $this->assertEquals(
            'Can not create dir /tmp/project_custom_temp_dir/vasya because it already exists',
            $dir->value()->error()->logMessage()
        );
    }

    protected function setUp(): void
    {
        (new Reset())->run();
    }
}