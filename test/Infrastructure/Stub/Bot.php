<?php

declare(strict_types=1);

namespace RC\Tests\Infrastructure\Stub;

use Exception;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutatingQueryWithMultipleValueSets;

class Bot
{
    private $connection;

    public function __construct(OpenConnection $connection)
    {
        $this->connection = $connection;
    }

    public function insert(array $records)
    {
        $response =
            (new SingleMutatingQueryWithMultipleValueSets(
                'insert into bot (id, token, is_private, name) values (?, ?, ?, ?)',
                array_map(
                    function (array $record) {
                        $values = array_merge($this->defaultValues(), $record);
                        return [$values['id'], $values['token'], $values['is_private'], $values['name']];
                    },
                    $records
                ),
                $this->connection
            ))
                ->response();
        if (!$response->isSuccessful()) {
            throw new Exception(sprintf('Error while inserting bot records: %s', $response->error()->logMessage()));
        }
    }

    private function defaultValues()
    {
        return [
            'is_private' => 0
        ];
    }
}