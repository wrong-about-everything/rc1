<?php

declare(strict_types=1);

namespace RC\Activities\Admin;

use Meringue\Timeline\Point\Now;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\Matches\ParticipantId2ParticipantsThatHeHasAlreadyMetWith;
use RC\Domain\Matches\PositionExperienceParticipantsInterestsMatrix\FromRound;
use RC\Domain\Matches\ReadModel\Impure\FromPure;
use RC\Domain\Matches\ReadModel\Impure\GeneratedMatchesForAllParticipants;
use RC\Domain\Matches\ReadModel\Impure\Matches;
use RC\Domain\Matches\ReadModel\Pure\FromArray;
use RC\Domain\MeetingRound\ReadModel\LatestNotYetStarted;
use RC\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use RC\Infrastructure\Logging\LogItem\InformationMessage;
use RC\Infrastructure\Logging\Logs;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\UserStory\Body\Arrray;
use RC\Infrastructure\UserStory\Body\Emptie;
use RC\Infrastructure\UserStory\Existent;
use RC\Infrastructure\UserStory\Response;
use RC\Infrastructure\UserStory\Response\NonRetryableServerError;
use RC\Infrastructure\UserStory\Response\Successful;

class SeesMatches extends Existent
{
    private $botId;
    private $connection;
    private $logs;

    public function __construct(BotId $botId, OpenConnection $connection, Logs $logs)
    {
        $this->botId = $botId;
        $this->connection = $connection;
        $this->logs = $logs;
    }

    public function response(): Response
    {
        $this->logs->receive(new InformationMessage('Admin sees matches scenario started'));

        $generatedMatchesForAllParticipants = $this->generatedMatchesForAllParticipants()->value();
        if (!$generatedMatchesForAllParticipants->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($generatedMatchesForAllParticipants));
            return new NonRetryableServerError(new Arrray(['error' => $generatedMatchesForAllParticipants->error()->logMessage()]));
        }

        $this->logs->receive(new InformationMessage('Admin sees matches scenario finished'));

        return
            new Successful(
                new Arrray(
                    $generatedMatchesForAllParticipants->pure()->isPresent()
                        ? $generatedMatchesForAllParticipants->pure()->raw()
                        : []
                )
            );
    }

    private function generatedMatchesForAllParticipants(): Matches
    {
        $latestMeetingRound = new LatestNotYetStarted($this->botId, new Now(), $this->connection);
        if (!$latestMeetingRound->value()->pure()->isPresent()) {
            return new FromPure(new FromArray([]));
        }

        return
            new GeneratedMatchesForAllParticipants(
                new FromRound(
                    $latestMeetingRound,
                    $this->connection
                ),
                (new ParticipantId2ParticipantsThatHeHasAlreadyMetWith($latestMeetingRound, $this->connection))->value()->pure()->raw()
            );
    }
}