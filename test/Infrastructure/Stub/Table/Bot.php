<?php

declare(strict_types=1);

namespace RC\Tests\Infrastructure\Stub\Table;

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
                'insert into bot (id, token, is_private, name, available_positions, available_experiences) values (?, ?, ?, ?, ?, ?)',
                array_map(
                    function (array $record) {
                        $values = array_merge($this->defaultValues(), $record);
                        return [
                            $values['id'],
                            $values['token'],
                            $values['is_private'],
                            $values['name'],
                            json_encode($values['available_positions']),
                            json_encode($values['available_experiences'])
                        ];
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
            'token' => 'secret_vasya',
            'is_private' => 0,
            'name' => 'vasya_bot',
            'available_positions' => [],
            'available_experiences' => [],
        ];
    }
}