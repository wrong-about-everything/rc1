<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\Filesystem\DirPath;

use PHPUnit\Framework\TestCase;
use RC\Infrastructure\Filesystem\DirPath\ExistentFromAbsolutePathString;
use RC\Infrastructure\Filesystem\FilePath\Created;
use RC\Tests\Infrastructure\Environment\Reset;

class ExistentFromAbsolutePathStringTest extends TestCase
{
    public function testExistenDirectory()
    {
        $this->markAsRisky('Continue here');
//        new Created(new FromStr, $contents)
        new ExistentFromAbsolutePathString('/tmp/kvass');
    }

    protected function setUp(): void
    {
        (new Reset())->run();
    }
}