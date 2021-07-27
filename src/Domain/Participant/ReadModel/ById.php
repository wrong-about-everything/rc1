<?php

declare(strict_types=1);

namespace RC\Domain\Participant\ReadModel;

use RC\Domain\Participant\ParticipantId\Impure\ParticipantId;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;

class ById implements Participant
{
    private $participantId;
    private $connection;
    private $cached;

    public function __construct(ParticipantId $participantId, OpenConnection $connection)
    {
        $this->participantId = $participantId;
        $this->connection = $connection;
        $this->cached = null;
    }

    public function value(): ImpureValue
    {
        return $this->concrete()->value();
    }

    public function exists(): ImpureValue
    {
        return $this->concrete()->exists();
    }

    private function concrete(): Participant
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doConcrete();
        }

        return $this->cached;
    }

    private function doConcrete(): Participant
    {
        $participant =
            (new Selecting(
                'select * from meeting_round_participant p where p.id = ?',
                [$this->participantId->value()->pure()->raw()],
                $this->connection
            ))
                ->response();
        if (!$participant->isSuccessful()) {
            return new NonSuccessful($participant);
        }
        if (!isset($participant->pure()->raw()[0])) {
            return new NonExistent();
        }

        return new FromArray($participant->pure()->raw()[0]);
    }
}