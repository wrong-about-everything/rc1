<?php

declare(strict_types=1);

namespace RC\Tests\Infrastructure\Stub\Table;

use Exception;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutatingQueryWithMultipleValueSets;

class UserRegistrationProgress
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
                'insert into user_registration_progress (registration_question_id, user_id) values (?, ?)',
                array_map(
                    function (array $record) {
                        $values = array_merge($this->defaultValues(), $record);
                        return [$values['registration_question_id'], $values['user_id']];
                    },
                    $records
                ),
                $this->connection
            ))
                ->response();
        if (!$response->isSuccessful()) {
            throw new Exception(sprintf('Error while inserting UserRegistrationProgress: %s', $response->error()->logMessage()));
        }
    }

    private function defaultValues()
    {
        return [];
    }
}