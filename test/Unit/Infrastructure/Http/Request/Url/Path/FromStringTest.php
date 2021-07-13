<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\Http\Request\Url\Path;

use PHPUnit\Framework\TestCase;
use RC\Infrastructure\Http\Request\Url\Path\FromString;

class FromStringTest extends TestCase
{
    public function testSuccess()
    {
        $this->assertEquals(
            'belov',
            (new FromString('belov'))->value()
        );
    }
}
