<?php

declare(strict_types=1);

namespace RC\Activities\Admin;

use Meringue\Timeline\Point\Now;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\Matches\ParticipantId2ParticipantsThatHeHasAlreadyMetWith;
use RC\Domain\Matches\PositionExperienceParticipantsInterestsMatrix\FromRound;
use RC\Domain\Matches\ReadModel\Impure\GeneratedMatchesForAllParticipants;
use RC\Domain\Matches\ReadModel\Impure\Matches;
use RC\Domain\MeetingRound\MeetingRoundId\Impure\FromMeetingRound;
use RC\Domain\MeetingRound\ReadModel\LatestNotYetStarted;
use RC\Domain\MeetingRound\ReadModel\MeetingRound;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful as SuccessfulValue;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use RC\Infrastructure\Logging\LogItem\InformationMessage;
use RC\Infrastructure\Logging\Logs;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use RC\Infrastructure\UserStory\Body\Arrray;
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

        $latestMeetingRound = new LatestNotYetStarted($this->botId, new Now(), $this->connection);
        if (!$latestMeetingRound->value()->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($latestMeetingRound->value()));
            return new NonRetryableServerError(new Arrray(['error' => $latestMeetingRound->value()->error()->logMessage()]));
        }
        if (!$latestMeetingRound->value()->pure()->isPresent()) {
            return new Successful(new Arrray([]));
        }

        $generatedMatchesForAllParticipants = $this->generatedMatchesForAllParticipants($latestMeetingRound)->value();
        if (!$generatedMatchesForAllParticipants->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($generatedMatchesForAllParticipants));
            return new NonRetryableServerError(new Arrray(['error' => $generatedMatchesForAllParticipants->error()->logMessage()]));
        }

        $impureParticipantToUsername = $this->participantToUsername($latestMeetingRound);
        if (!$impureParticipantToUsername->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($impureParticipantToUsername));
            return new NonRetryableServerError(new Arrray(['error' => $impureParticipantToUsername->error()->logMessage()]));
        }

        $this->logs->receive(new InformationMessage('Admin sees matches scenario finished'));

        return
            new Successful(
                new Arrray(
                    array_map(
                        function (array $pair) use ($impureParticipantToUsername) {
                            return [
                                $impureParticipantToUsername->pure()->raw()[$pair[0]],
                                $impureParticipantToUsername->pure()->raw()[$pair[1]]
                            ];
                        },
                        $generatedMatchesForAllParticipants->pure()->raw()['matches']
                    )
                )
            );
    }

    private function generatedMatchesForAllParticipants(MeetingRound $latestMeetingRound): Matches
    {
        return
            new GeneratedMatchesForAllParticipants(
                new FromRound(
                    $latestMeetingRound,
                    $this->connection
                ),
                (new ParticipantId2ParticipantsThatHeHasAlreadyMetWith($latestMeetingRound, $this->connection))->value()->pure()->raw()
            );
    }

    private function participantToUsername(MeetingRound $meetingRound): ImpureValue
    {
        $impureParticipantToUsername =
            (new Selecting(
                <<<qqqqqqqqqqqqqqq
select mrp.id, tu.telegram_handle
from telegram_user tu
    join meeting_round_participant mrp on tu.id = mrp.user_id
where mrp.meeting_round_id = ?
qqqqqqqqqqqqqqq
                ,
                [(new FromMeetingRound($meetingRound))->value()->pure()->raw()],
                $this->connection
            ))
                ->response();
        if (!$impureParticipantToUsername->isSuccessful()) {
            return $impureParticipantToUsername;
        }

        return
            new SuccessfulValue(
                new Present(
                    array_reduce(
                        $impureParticipantToUsername->pure()->raw(),
                        function (array $participantToUsername, array $row) {
                            return $participantToUsername + [$row['id'] => $row['telegram_handle']];
                        },
                        []
                    )
                )
            );
    }
}