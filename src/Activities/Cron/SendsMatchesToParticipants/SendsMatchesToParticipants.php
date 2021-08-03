<?php

declare(strict_types=1);

namespace RC\Activities\Cron\SendsMatchesToParticipants;

use Meringue\Timeline\Point\Now;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\Bot\BotToken\Impure\ByBotId;
use RC\Domain\Matches\PositionExperienceParticipantsInterestsMatrix\FromRound;
use RC\Domain\Matches\ReadModel\Impure\GeneratedMatchesForAllParticipants;
use RC\Domain\Matches\WriteModel\Impure\Saved;
use RC\Domain\Matches\ReadModel\Impure\MatchesForRound;
use RC\Domain\MeetingRound\MeetingRoundId\Impure\FromMeetingRound;
use RC\Domain\MeetingRound\ReadModel\LatestAlreadyStarted;
use RC\Domain\MeetingRound\ReadModel\MeetingRound;
use RC\Domain\Participant\ParticipantId\Pure\FromString;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\Logging\LogItem\ErrorMessage;
use RC\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use RC\Infrastructure\Logging\LogItem\InformationMessage;
use RC\Infrastructure\Logging\LogItem\InformationMessageWithData;
use RC\Infrastructure\Logging\Logs;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use RC\Infrastructure\TelegramBot\UserId\Pure\FromInteger;
use RC\Infrastructure\UserStory\Body\Emptie;
use RC\Infrastructure\UserStory\Existent;
use RC\Infrastructure\UserStory\Response;
use RC\Infrastructure\UserStory\Response\RetryableServerError;
use RC\Infrastructure\UserStory\Response\Successful;

class SendsMatchesToParticipants extends Existent
{
    private $botId;
    private $transport;
    private $connection;
    private $logs;

    public function __construct(BotId $botId, HttpTransport $transport, OpenConnection $connection, Logs $logs)
    {
        $this->botId = $botId;
        $this->transport = $transport;
        $this->connection = $connection;
        $this->logs = $logs;
    }

    public function response(): Response
    {
        $this->logs->receive(new InformationMessage('Cron sends matches to participants scenario started'));

        $currentRound = new LatestAlreadyStarted($this->botId, new Now(), $this->connection);
        if (!$currentRound->value()->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($currentRound->value()));
            return new Successful(new Emptie());
        }
        if (!$currentRound->value()->pure()->isPresent()) {
            $this->logs->receive(new ErrorMessage('There is no active meeting round. Check both cron and round start datetime in database.'));
            return new Successful(new Emptie());
        }

        if ($this->noMatchesGeneratedForCurrentRound($currentRound)) {
            $this->logs->receive(new InformationMessage('Generating matches for current round...'));
            $value = $this->savedMatches($currentRound)->value();
            if (!$value->isSuccessful()) {
                $this->logs->receive(new FromNonSuccessfulImpureValue($value));
                return new RetryableServerError(new Emptie());
            }
            $this->logs->receive(new InformationMessageWithData('Matches generated', $value->pure()->raw()));
        }

        array_map(
            function (array $matchingPair) {
                $participantValue =
                    (new NotifiedParticipant(
                        new FromString($matchingPair['participant_id']),
                        new FromInteger($matchingPair['participant_telegram_id']),
                        (new Text(
                            $matchingPair['participant_first_name'],
                            $matchingPair['match_first_name'],
                            $matchingPair['match_telegram_handle'],
                            json_decode($matchingPair['participant_interested_in'] ?? json_encode([])),
                            json_decode($matchingPair['match_interested_in'] ?? json_encode([])),
                            $matchingPair['about_match'],
                        ))
                            ->value(),
                        new ByBotId($this->botId, $this->connection),
                        $this->transport,
                        $this->connection
                    ))
                        ->value();
                if (!$participantValue->isSuccessful()) {
                    $this->logs->receive(new FromNonSuccessfulImpureValue($participantValue));
                }
            },
            $this->matchesToSend($currentRound)
        );

        $this->logs->receive(new InformationMessage('Cron sends matches to participants scenario finished'));

        return new Successful(new Emptie());
    }

    private function noMatchesGeneratedForCurrentRound(MeetingRound $currentRound)
    {
        return !(new MatchesForRound($currentRound, $this->connection))->value()->pure()->isPresent();
    }

    private function savedMatches(MeetingRound $currentRound)
    {
        return
            new Saved(
                new GeneratedMatchesForAllParticipants(
                    new FromRound($currentRound, $this->connection)
                ),
                $this->connection
            );
    }

    private function matchesToSend(MeetingRound $currentRound)
    {
        return
            (new Selecting(
                <<<q
select
    pair.participant_id participant_id,
    user_to.telegram_id participant_telegram_id,
    user_to.first_name participant_first_name,
    match_user.first_name match_first_name,
    match_user.telegram_handle match_telegram_handle,
    participant_to.interested_in participant_interested_in,
    match_participant.interested_in match_interested_in,
    bu.about about_match,
    pair.match_participant_contacts_sent
from meeting_round_pair pair
    join meeting_round_participant participant_to on pair.participant_id = participant_to.id
    join meeting_round_participant match_participant on pair.match_participant_id = match_participant.id
    join "telegram_user" user_to on participant_to.user_id = user_to.id
    join "telegram_user" match_user on match_participant.user_id = match_user.id
    join meeting_round mr on mr.id = participant_to.meeting_round_id
    join bot_user bu on bu.user_id = match_user.id and bu.bot_id = mr.bot_id
where participant_to.meeting_round_id = ? and match_participant.meeting_round_id = ? and pair.match_participant_contacts_sent = false
limit 100
q
                ,
                [(new FromMeetingRound($currentRound))->value()->pure()->raw(), (new FromMeetingRound($currentRound))->value()->pure()->raw()],
                $this->connection
            ))
                ->response()->pure()->raw();
    }
}