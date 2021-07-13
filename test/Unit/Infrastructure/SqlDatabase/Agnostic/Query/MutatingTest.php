<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\SqlDatabase\Agnostic\Query;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\Credentials\DefaultCredentials;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\DefaultConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\Host\FromString;
use Exception;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Mutating;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\OrdersDeliveryConnection;

class MutatingTest extends TestCase
{
    public function testSuccessfulInsert()
    {
        $result =
            (new Mutating(
                'insert into _order values (?, ?)',
                array(
                    Uuid::uuid4()->toString(),
                    json_encode([])
                )
            ))
                ->result(
                    (new OrdersDeliveryConnection())
                        ->open()
                )
        ;

        $this->assertTrue($result->isSuccessful());
        $this->assertEquals([], $result->value());
    }

    /**
     * @dataProvider invalidQuery
     */
    public function testInsertWithInvalidQuery(string $query)
    {
        $result =
            (new Mutating(
                $query,
                array(
                    Uuid::uuid4()->toString(),
                    json_encode([])
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
            ['inssssssert into _order values (?, ?)'],
            ['insert into orrrrrrder values (?, ?)'],
            ['insert into _order values (?, ?, ?)'],
            ['insert into _order values (?)'],
            ['insert into _order values (?'],
        ];
    }
}
