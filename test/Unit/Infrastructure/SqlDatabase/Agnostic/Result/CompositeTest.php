<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\SqlDatabase\Agnostic\Result;

use PHPUnit\Framework\TestCase;
use RC\Infrastructure\SqlDatabase\Agnostic\Result\Composite;
use RC\Infrastructure\SqlDatabase\Agnostic\Result\Failed;
use RC\Infrastructure\SqlDatabase\Agnostic\Result\Successful;
use Exception;

class CompositeTest extends TestCase
{
    public function testWithSuccessfulResults()
    {
        $compositeResult =
            new Composite(
                new Successful(['vasya']),
                new Successful(['belov'])
            );

        $this->assertTrue($compositeResult->isSuccessful());
        $this->assertEquals(['vasya', 'belov'], $compositeResult->value());
    }

    public function testWithFailedResult()
    {
        $compositeResult =
            new Composite(
                new Successful(['vasya']),
                new Failed('belov')
            );

        $this->assertFalse($compositeResult->isSuccessful());
        $this->assertEquals('belov', $compositeResult->error());
        try {
            $compositeResult->value();
        } catch (Exception $e) {
            return $this->assertTrue(true);
        }

        $this->fail('Value should not have been obtained since a failed result is involved');
    }
}
