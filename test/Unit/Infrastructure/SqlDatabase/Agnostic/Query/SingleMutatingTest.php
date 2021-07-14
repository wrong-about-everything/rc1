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

class SingleMutatingTest extends TestCase
{
    public function testSuccessfulInsert()
    {
        $result =
            (new SingleMutating(
                'insert into sample_table values (?, ?)',
                [
                    Uuid::uuid4()->toString(),
                    'hello, vasya!'
                ],
                new ApplicationConnection()
            ))
                ->response();

        $this->assertTrue($result->isSuccessful());
        $this->assertFalse($result->pure()->isPresent());
    }

    public function testSuccessfulUpdate()
    {
        $connection = new ApplicationConnection();
        $this->insert($connection);
        $result =
            (new SingleMutating(
                'update sample_table set test_field = ? where test_field in (?)',
                [
                    'hello, vasya!',
                    ['fedya', 'vasya']
                ],
                $connection
            ))
                ->response();

        $this->assertTrue($result->isSuccessful());
        $this->assertFalse($result->pure()->isPresent());
        $this->assertEquals(
            [['test_field' => 'hello, vasya!'], ['test_field' => 'hello, vasya!'], ['test_field' => 'tolya']],
            (new Selecting(
                'select test_field from sample_table order by test_field asc',
                [],
                $connection
            ))
                ->response()->pure()->raw()
        );
    }

    /**
     * @dataProvider invalidQuery
     */
    public function testInsertWithInvalidQuery(string $query)
    {
        $result =
            (new SingleMutating(
                $query,
                [
                    Uuid::uuid4()->toString(),
                    json_encode([])
                ],
                new ApplicationConnection()
            ))
                ->response();

        $this->assertFalse($result->isSuccessful());
        $this->assertNotEmpty($result->error());
    }

    public function invalidQuery()
    {
        return [
            ['inssssssert into non_existent_table values (?, ?)'],
            ['insert into orrrrrrder values (?, ?)'],
            ['insert into non_existent_table values (?, ?, ?)'],
            ['insert into non_existent_table values (?)'],
            ['insert into non_existent_table values (?'],
        ];
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }

    private function insert(OpenConnection $connection)
    {
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
