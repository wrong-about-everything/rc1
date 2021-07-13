<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\SqlDatabase\Agnostic\Query;

use PHPUnit\Framework\TestCase;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\FlatValues;

class FlatValuesTest extends TestCase
{
    public function test()
    {
        $this->assertEquals(
            [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14],
            (new FlatValues([1, [2, 3], [4, [5, [6, [7, 8, 9], 10], 11, [12, [13]]]], 14]))->value()
        );
    }
}
