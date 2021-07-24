<?php

declare(strict_types=1);

namespace RC\Domain\Bot;

use RC\Domain\Bot\BotId\BotId;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;

class ById implements Bot
{
    private $botId;
    private $connection;
    private $cached;

    public function __construct(BotId $botId, OpenConnection $connection)
    {
        $this->botId = $botId;
        $this->connection = $connection;
        $this->cached = null;
    }

    public function value(): ImpureValue
    {
        return $this->cached()->value();
    }

    public function exists(): ImpureValue
    {
        return $this->cached()->exists();
    }

    private function cached()
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doCached();
        }

        return $this->cached;
    }

    private function doCached()
    {
        $response =
            (new Selecting(
                'select * from bot where id = ?',
                [$this->botId->value()],
                $this->connection
            ))
                ->response();
        if (!$response->isSuccessful()) {
            return new NonSuccessful($response);
        }
        if (!$response->pure()->isPresent()) {
            return new NonExistent();
        }

        return new FromArray($response->pure()->raw()[0]);
    }
}