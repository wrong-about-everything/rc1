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
use RC\Infrastructure\SqlDatabase\Agnostic\Query\TransactionalQueryFromMultipleQueries;
use RC\Tests\Infrastructure\Environment\Reset;

class TransactionalQueryFromMultipleQueriesTest extends TestCase
{
    public function testSuccessfullyInsertTwoRecords()
    {
        $uuid1 = Uuid::uuid4()->toString();
        $uuid2 = Uuid::uuid4()->toString();
        $connection = new ApplicationConnection();

        $result =
            (new TransactionalQueryFromMultipleQueries(
                [
                    new SingleMutating(
                        'insert into sample_table values (?, ?)',
                        [$uuid1, 'vasya'],
                        $connection
                    ),
                    new SingleMutating(
                        'insert into sample_table values (?, ?)',
                        [$uuid2, 'fedya'],
                        $connection
                    )
                ],
                $connection
            ))
                    ->response();

        $this->assertTrue($result->isSuccessful());
        $this->assertRecordExists($uuid1, $connection);
        $this->assertRecordExists($uuid2, $connection);
    }

    public function testRollbackSyntaxErrorInsert()
    {
        $uuid1 = Uuid::uuid4()->toString();
        $uuid2 = Uuid::uuid4()->toString();
        $connection = new ApplicationConnection();

        $result =
            (new TransactionalQueryFromMultipleQueries(
                [
                    new SingleMutating(
                        'insert into sample_table values (?, ?)',
                        [$uuid1, 'vasya'],
                        $connection
                    ),
                    new SingleMutating(
                        'inssssssssssssssssssssssssssssssert into sample_table values (?, ?)',
                        [$uuid2, 'fedya'],
                        $connection
                    )
                ],
                $connection
            ))
                ->response();

        $this->assertFalse($result->isSuccessful());
        $this->assertRecordDoesNotExist($uuid1, $connection);
        $this->assertRecordDoesNotExist($uuid2, $connection);
    }

    public function testRollbackOnDuplication()
    {
        $uuid = Uuid::uuid4()->toString();
        $connection = new ApplicationConnection();

        $result =
            (new TransactionalQueryFromMultipleQueries(
                [
                    new SingleMutating(
                        'insert into sample_table values (?, ?)',
                        [$uuid, 'vasya'],
                        $connection
                    ),
                    new SingleMutating(
                        'insert into sample_table values (?, ?)',
                        [$uuid, 'fedya'],
                        $connection
                    )
                ],
                $connection
            ))
                ->response();

        $this->assertFalse($result->isSuccessful());
        $this->assertRecordDoesNotExist($uuid, $connection);
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }

    private function assertRecordExists(string $uuid, OpenConnection $connection)
    {
        $result =
            (new Selecting(
                'select from sample_table where id = ?',
                [$uuid],
                $connection
            ))
                ->response();

        $this->assertTrue($result->isSuccessful());
        $this->assertNotEmpty($result->pure()->raw());
    }

    private function assertRecordDoesNotExist(string $uuid, OpenConnection $connection)
    {
        $result =
            (new Selecting(
                'select from sample_table where id = ?',
                [$uuid],
                $connection
            ))
                ->response();

        $this->assertTrue($result->isSuccessful());
        $this->assertEmpty($result->pure()->raw());
    }
}
