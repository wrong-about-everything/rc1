<?php

declare(strict_types=1);

namespace RC\Tests\Infrastructure\Stub\Table;

use Exception;
use Ramsey\Uuid\Uuid;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutatingQueryWithMultipleValueSets;

class MeetingRoundInvitation
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
                'insert into "meeting_round_invitation" (id, meeting_round_id, user_id, status) values (?, ?, ?, ?)',
                array_map(
                    function (array $record) {
                        $values = array_merge($this->defaultValues(), $record);
                        return [$values['id'], $values['meeting_round_id'], $values['user_id'], $values['status']];
                    },
                    $records
                ),
                $this->connection
            ))
                ->response();
        if (!$response->isSuccessful()) {
            throw new Exception(sprintf('Error while inserting meeting_round_invitation record: %s', $response->error()->logMessage()));
        }
    }

    private function defaultValues()
    {
        return [
            'id' => Uuid::uuid4()->toString()
        ];
    }
}