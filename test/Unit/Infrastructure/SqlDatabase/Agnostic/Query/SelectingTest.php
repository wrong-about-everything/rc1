<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\SqlDatabase\Agnostic\Query;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Mutating;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\OrdersDeliveryConnection;
use RC\Tests\Infrastructure\Storage\Postgres\ResetOmsEnvironment;

class SelectingTest extends TestCase
{
    public function testSelectSuccessfullyEmptyData()
    {
        $result =
            (new Selecting(
                'select from _order where id = ?',
                array(
                    Uuid::uuid4()->toString()
                )
            ))
                ->result(
                    (new OrdersDeliveryConnection())
                        ->open()
                )
        ;

        $this->assertTrue($result->isSuccessful());
        $this->assertEmpty($result->value());
    }

    public function testSelectSuccessfullyWithASingleValueInsideInClause()
    {
        $result =
            (new Selecting(
                'select from _order where id in (?)',
                [Uuid::uuid4()->toString()]
            ))
                ->result(
                    (new OrdersDeliveryConnection())
                        ->open()
                )
        ;

        $this->assertTrue($result->isSuccessful());
        $this->assertEmpty($result->value());
    }

    /**
     * @dataProvider parametersInArrays
     */
    public function testSelectSuccessfullyWithSeveralValuesInsideInClause(string $query, array $parameters, array $uuids)
    {
        foreach ($uuids as $uuid) {
            $this->seed($uuid);
        }

        $result =
            (new Selecting(
                $query,
                $parameters
            ))
                ->result(
                    (new OrdersDeliveryConnection())
                        ->open()
                )
        ;

        $this->assertTrue($result->isSuccessful());
        $this->assertNotEmpty($result->value());
    }

    public function parametersInArrays()
    {
        $uuid1 = Uuid::uuid4()->toString();
        $uuid2 = Uuid::uuid4()->toString();
        $uuid3 = Uuid::uuid4()->toString();
        $uuid4 = Uuid::uuid4()->toString();

        return
            [
                [
                    'select from _order where id = ? or id in (?) or id = ?',
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
                    'select from _order where id = ? or id in (?) or id = ? or id in (?)',
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
                    'select from _order where id = ? or id in (?) or id in (?)',
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
                    'select from _order where id = ? or id = ? or id in (?)',
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
                    'select from _order where id in (?) or id in (?) or id in (?)',
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
                    'select id from _order where id in (?) or id = ? or id in (?)',
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

    protected function setUp()
    {
        (new ResetOmsEnvironment(new OrdersDeliveryConnection()))->run();
    }

    public function testSelectSuccessfullyNonEmptyData()
    {
        $uuid = Uuid::uuid4()->toString();
        $this->seed($uuid);

        $result =
            (new Selecting(
                'select from _order where id = ?',
                array($uuid)
            ))
                ->result(
                    (new OrdersDeliveryConnection())
                        ->open()
                )
        ;

        $this->assertTrue($result->isSuccessful());
        $this->assertNotEmpty($result->value());
    }

    /**
     * @dataProvider invalidQuery
     */
    public function testInsertWithInvalidQuery(string $query)
    {
        $result =
            (new Selecting(
                $query,
                array(
                    Uuid::uuid4()->toString()
                )
            ))
                ->result(
                    (new OrdersDeliveryConnection())
                        ->open()
                )
        ;

        $this->assertFalse($result->isSuccessful());
        $this->assertNotEmpty($result->error());
    }

    public function invalidQuery()
    {
        return [
            ['ssssssssssselect from _order where id = ?'],
            ['select from "orrrrrrrrrrrrrrrder" where id = ?'],
            ['select from _order where idddddddddddd = ?'],
            ['select from _order where idddddddddddd = (?'],
        ];
    }

    private function seed(string $uuid)
    {
        (new Mutating(
            'insert into _order values (?, ?)',
            array(
                $uuid,
                json_encode([])
            )
        ))
            ->result(
                (new OrdersDeliveryConnection())
                    ->open()
            );
    }
}
