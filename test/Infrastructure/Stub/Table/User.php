<?php

declare(strict_types=1);

namespace RC\Tests\Infrastructure\Stub\Table;

use Exception;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutatingQueryWithMultipleValueSets;

class User
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
                'insert into "user" (id, first_name, last_name, telegram_id, telegram_handle) values (?, ?, ?, ?, ?)',
                array_map(
                    function (array $record) {
                        $values = array_merge($this->defaultValues(), $record);
                        return [
                            $values['id'],
                            $values['first_name'],
                            $values['last_name'],
                            $values['telegram_id'],
                            $values['telegram_handle']
                        ];
                    },
                    $records
                ),
                $this->connection
            ))
                ->response();
        if (!$response->isSuccessful()) {
            throw new Exception(sprintf('Error while inserting user records: %s', $response->error()->logMessage()));
        }
    }

    private function defaultValues()
    {
        return [
            'first_name' => 'Vasya',
            'last_name' => 'Belov',
            'telegram_id' => 666,
            'telegram_handle' => '@vasya',
        ];
    }
}