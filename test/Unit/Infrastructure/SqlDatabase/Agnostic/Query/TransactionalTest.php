<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\SqlDatabase\Agnostic\Query;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Mutating;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Transactional;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\OrdersDeliveryConnection;

class TransactionalTest extends TestCase
{
    public function testSuccessfullyInsertTwoRecords()
    {
        $uuid1 = Uuid::uuid4()->toString();
        $uuid2 = Uuid::uuid4()->toString();

        $result =
            (new Transactional(
                new Mutating(
                    'insert into _order values (?, ?)',
                    array(
                        $uuid1,
                        json_encode([])
                    )
                ),
                new Mutating(
                    'insert into _order values (?, ?)',
                    array(
                        $uuid2,
                        json_encode([])
                    )
                )))
                    ->result(
                        (new OrdersDeliveryConnection())
                            ->open()
                    )
        ;

        $this->assertTrue($result->isSuccessful());
        $this->assertOrderExists($uuid1);
        $this->assertOrderExists($uuid2);
    }

    public function testRollbackUnsuccessfulInsert()
    {
        $uuid1 = Uuid::uuid4()->toString();
        $uuid2 = Uuid::uuid4()->toString();

        $result =
            (new Transactional(
                new Mutating(
                    'insert into _order values (?, ?)',
                    array(
                        $uuid1,
                        json_encode([])
                    )
                ),
                new Mutating(
                    'inssssssssssssssssssssssssssssssert into _order values (?, ?)',
                    array(
                        $uuid2,
                        json_encode([])
                    )
                )))
                ->result(
                    (new OrdersDeliveryConnection())
                        ->open()
                )
        ;

        $this->assertFalse($result->isSuccessful());
        $this->assertOrderDoesNotExist($uuid1);
        $this->assertOrderDoesNotExist($uuid2);
    }

    public function testRollbackOnDuplication()
    {
        $uuid = Uuid::uuid4()->toString();

        $result =
            (new Transactional(
                new Mutating(
                    'insert into _order values (?, ?)',
                    array(
                        $uuid,
                        json_encode([])
                    )
                ),
                new Mutating(
                    'insert into _order values (?, ?)',
                    array(
                        $uuid,
                        json_encode([])
                    )
                )))
                ->result(
                    (new OrdersDeliveryConnection())
                        ->open()
                )
        ;

        $this->assertFalse($result->isSuccessful());
        $this->assertOrderDoesNotExist($uuid);
    }

    private function assertOrderExists(string $uuid)
    {
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

    private function assertOrderDoesNotExist(string $uuid)
    {
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
        $this->assertEmpty($result->value());
    }
}
