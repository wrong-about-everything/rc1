<?php

declare(strict_types=1);

namespace RC\Domain\Infrastructure\Setup\Database;

use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use RC\Infrastructure\Uuid\RandomUUID;

class Seed
{
    private $connection;

    public function __construct(OpenConnection $connection)
    {
        $this->connection = $connection;
    }

    public function value(): ImpureValue
    {
        return
            (new SingleMutating(
                'insert into sample_table values (?, ?)',
                [(new RandomUUID())->value(), 'vasya'],
                $this->connection
            ))
                ->response();
    }
}