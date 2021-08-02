<?php

declare(strict_types=1);

namespace RC\Tests\Infrastructure\Stub\Table;

use Exception;
use Ramsey\Uuid\Uuid;
use RC\Domain\Experience\ExperienceId\Pure\LessThanAYear;
use RC\Domain\Position\PositionId\Pure\ProductManager;
use RC\Domain\User\UserStatus\Pure\Registered;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutatingQueryWithMultipleValueSets;

class BotUser
{
    private $connection;

    public function __construct(OpenConnection $connection)
    {
        $this->connection = $connection;
    }

    public function insert(array $records)
    {
        $botUserInsertResponse =
            (new SingleMutatingQueryWithMultipleValueSets(
                'insert into "bot_user" (id, user_id, bot_id, position, experience, about, status) values (?, ?, ?, ?, ?, ?, ?)',
                array_map(
                    function (array $record) {
                        $values = array_merge($this->defaultValues(), $record);
                        return [
                            $values['id'],
                            $values['user_id'],
                            $values['bot_id'],
                            $values['position'],
                            $values['experience'],
                            $values['about'],
                            $values['status']
                        ];
                    },
                    $records
                ),
                $this->connection
            ))
                ->response();
        if (!$botUserInsertResponse->isSuccessful()) {
            throw new Exception(sprintf('Error while inserting bot_user record: %s', $botUserInsertResponse->error()->logMessage()));
        }
    }

    private function defaultValues()
    {
        return [
            'id' => Uuid::uuid4()->toString(),
            'position' => (new ProductManager())->value(),
            'experience' => (new LessThanAYear())->value(),
            'about' => 'About me',
            'status' => (new Registered())->value(),
        ];
    }
}