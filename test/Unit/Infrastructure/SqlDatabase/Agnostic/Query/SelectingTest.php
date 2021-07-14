<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\SqlDatabase\Agnostic\Query;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutatingQueryWithMultipleValueSets;
use RC\Tests\Infrastructure\Environment\Reset;

class SelectingTest extends TestCase
{
    public function testWhenThereIsNoDataThenEmptyArrayReturned()
    {
        $response =
            (new Selecting(
                'select id from sample_table where id = ?',
                [Uuid::uuid4()->toString()],
                new ApplicationConnection()
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertEmpty($response->pure()->raw());
    }

    public function testSelectSuccessfullyWithASingleValueInsideInClause()
    {
        $response =
            (new Selecting(
                'select id from sample_table where id in (?)',
                [Uuid::uuid4()->toString()],
                new ApplicationConnection()
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertEmpty($response->pure()->raw());
    }

    /**
     * @dataProvider parametersInArrays
     */
    public function testSelectSuccessfullyWithSeveralValuesInsideInClause(string $query, array $parameters, array $uuids)
    {
        $connection = new ApplicationConnection();
        $this->seed($uuids, $connection);

        $response =
            (new Selecting(
                $query,
                $parameters,
                $connection
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertNotEmpty($response->pure()->raw());
        $this->assertEquals(
            array_map(
                function (string $uuid) {
                    return ['id' => $uuid];
                },
                $uuids
            ),
            $response->pure()->raw()
        );
    }

    public function parametersInArrays()
    {
        $uuid1 = Uuid::uuid4()->toString();
        $uuid2 = Uuid::uuid4()->toString();
        $uuid3 = Uuid::uuid4()->toString();
        $uuid4 = Uuid::uuid4()->toString();

        return [
            [
                'select id from sample_table where id = ? or id in (?) or id = ?',
                [
                    $uuid1,
                    [$uuid2, $uuid3],
                    $uuid4,
                ],
                [
                    $uuid1,
                    $uuid2,
                    $uuid3,
                    $uuid4
                ]
            ],
            [
                'select id from sample_table where id = ? or id in (?) or id = ? or id in (?)',
                [
                    $uuid1,
                    [$uuid2, $uuid3],
                    $uuid4,
                    [$uuid1, $uuid2],
                ],
                [
                    $uuid1,
                    $uuid2,
                    $uuid3,
                    $uuid4
                ]
            ],
            [
                'select id from sample_table where id = ? or id in (?) or id in (?)',
                [
                    $uuid1,
                    [$uuid2, $uuid3],
                    [$uuid4],
                ],
                [
                    $uuid1,
                    $uuid2,
                    $uuid3,
                    $uuid4
                ]
            ],
            [
                'select id from sample_table where id = ? or id = ? or id in (?)',
                [
                    $uuid1,
                    $uuid4,
                    [$uuid2, $uuid3],
                ],
                [
                    $uuid1,
                    $uuid2,
                    $uuid3,
                    $uuid4
                ]
            ],
            [
                'select id from sample_table where id in (?) or id in (?) or id in (?)',
                [
                    [$uuid1],
                    [$uuid2, $uuid3],
                    [$uuid4],
                ],
                [
                    $uuid1,
                    $uuid2,
                    $uuid3,
                    $uuid4,
                ]
            ],
            [
                'select id from sample_table where id in (?) or id = ? or id in (?)',
                [
                    0 => [$uuid1, $uuid2],
                    1 => $uuid3,
                    3 => [$uuid4],
                ],
                [
                    $uuid1,
                    $uuid2,
                    $uuid3,
                    $uuid4,
                ]
            ]
        ];
    }

    /**
     * @dataProvider invalidQuery
     */
    public function testInsertWithInvalidQuery(string $query)
    {
        $result =
            (new Selecting(
                $query,
                [Uuid::uuid4()->toString()],
                new ApplicationConnection()
            ))
                ->response();

        $this->assertFalse($result->isSuccessful());
        $this->assertNotEmpty($result->error());
    }

    public function invalidQuery()
    {
        return [
            ['ssssssssssselect id from sample_table where id = ?'],
            ['select id from "orrrrrrrrrrrrrrrder" where id = ?'],
            ['select id from sample_table where idddddddddddd = ?'],
            ['select id from sample_table where idddddddddddd = (?'],
        ];
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }

    private function seed(array $uuids, OpenConnection $connection)
    {
        (new SingleMutatingQueryWithMultipleValueSets(
            'insert into sample_table values (?, ?)',
            array_map(
                function (string $uuid) {
                    return [
                        $uuid,
                        sprintf('hey, %s', $uuid)
                    ];
                },
                $uuids
            ),
            $connection
        ))
            ->response();
    }
}
