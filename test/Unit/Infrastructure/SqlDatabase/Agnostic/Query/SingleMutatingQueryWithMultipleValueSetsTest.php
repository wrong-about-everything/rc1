<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\SqlDatabase\Agnostic\Query;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutatingQueryWithMultipleValueSets;
use RC\Tests\Infrastructure\Environment\Reset;

class SingleMutatingQueryWithMultipleValueSetsTest extends TestCase
{
    public function testSuccessfulInsert()
    {
        $connection = new ApplicationConnection();

        $response = $this->insert($connection);

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->pure()->isPresent());
        $this->assertEquals(
            [['test_field' => 'fedya'], ['test_field' => 'tolya'], ['test_field' => 'vasya']],
            (new Selecting(
                'select test_field from sample_table order by test_field asc',
                [],
                $connection
            ))
                ->response()->pure()->raw()
        );
    }

    public function testSuccessfulUpdate()
    {
        $connection = new ApplicationConnection();
        $this->insert($connection);
        $result =
            (new SingleMutatingQueryWithMultipleValueSets(
                'update sample_table set test_field = ? where test_field = ?',
                [
                    ['hello, vasya', 'vasya'],
                    ['hello, fedya', 'fedya'],
                    ['hello, tolya', 'tolya'],
                ],
                $connection
            ))
                ->response();

        $this->assertTrue($result->isSuccessful());
        $this->assertFalse($result->pure()->isPresent());
        $this->assertEquals(
            [['test_field' => 'hello, fedya'], ['test_field' => 'hello, tolya'], ['test_field' => 'hello, vasya']],
            (new Selecting(
                'select test_field from sample_table order by test_field asc',
                [],
                $connection
            ))
                ->response()->pure()->raw()
        );
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }

    private function insert(OpenConnection $connection)
    {
        return
            (new SingleMutatingQueryWithMultipleValueSets(
                'insert into sample_table values (?, ?)',
                [
                    [
                        Uuid::uuid4()->toString(),
                        'vasya'
                    ],
                    [
                        Uuid::uuid4()->toString(),
                        'fedya'
                    ],
                    [
                        Uuid::uuid4()->toString(),
                        'tolya'
                    ],
                ],
                $connection
            ))
                ->response();
    }
}
