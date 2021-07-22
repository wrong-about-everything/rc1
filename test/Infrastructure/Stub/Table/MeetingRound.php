<?php

declare(strict_types=1);

namespace RC\Tests\Infrastructure\Stub\Table;

use Exception;
use Meringue\ISO8601DateTime\PhpSpecificTimeZone\Moscow;
use Meringue\Timeline\Point\Now;
use Ramsey\Uuid\Uuid;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutatingQueryWithMultipleValueSets;

class MeetingRound
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
                'insert into "meeting_round" (id, bot_id, name, start_date, timezone) values (?, ?, ?, ?, ?)',
                array_map(
                    function (array $record) {
                        $values = array_merge($this->defaultValues(), $record);
                        return [$values['id'], $values['bot_id'], $values['name'], $values['start_date'], $values['timezone']];
                    },
                    $records
                ),
                $this->connection
            ))
                ->response();
        if (!$response->isSuccessful()) {
            throw new Exception(sprintf('Error while inserting meeting_round record: %s', $response->error()->logMessage()));
        }
    }

    private function defaultValues()
    {
        return [
            'id' => Uuid::uuid4()->toString(),
            'name' => 'New meeting round',
            'start_date' => (new Now())->value(),
            'timezone' => (new Moscow())->value(),
        ];
    }
}