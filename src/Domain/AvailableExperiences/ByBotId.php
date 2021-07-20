<?php

declare(strict_types=1);

namespace RC\Domain\AvailableExperiences;

use RC\Domain\BotId\BotId;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;

class ByBotId implements AvailableExperiences
{
    private $botId;
    private $connection;

    public function __construct(BotId $botId, OpenConnection $connection)
    {
        $this->botId = $botId;
        $this->connection = $connection;
    }

    public function value(): ImpureValue
    {
        $r =
            (new Selecting(
                'select available_experiences from bot where id = ?',
                [$this->botId->value()],
                $this->connection
            ))
                ->response();
        if (!$r->isSuccessful()) {
            return $r;
        }
        $rawAvailableExperiences = $r->pure()->raw()[0]['available_experiences'];
        if (is_null($rawAvailableExperiences)) {
            return new Successful(new Emptie());
        }

        return new Successful(new Present(json_decode($rawAvailableExperiences, true)));
    }
}