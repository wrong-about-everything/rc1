<?php

declare(strict_types=1);

namespace RC\Activities\Cron\SendsMatchesToParticipants;

use Meringue\Timeline\Point\Now;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\Bot\BotId\FromUuid;
use RC\Domain\Matches\PositionExperienceParticipantsInterestsMatrix\FromRound;
use RC\Domain\Matches\ReadModel\Impure\GeneratedMatchesForAllParticipants;
use RC\Domain\Matches\WriteModel\Impure\Saved;
use RC\Domain\Matches\ReadModel\Pure\GeneratedMatchesForSegment;
use RC\Domain\Matches\ReadModel\Impure\Matches;
use RC\Domain\Matches\ReadModel\Impure\MatchesForRound;
use RC\Domain\MeetingRound\ReadModel\ByBotIdAndStartDateTime;
use RC\Domain\MeetingRound\ReadModel\MeetingRound;
use RC\Domain\Participant\Status\Pure\Registered;
use RC\Domain\RoundInvitation\Status\Pure\Sent as SentStatus;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Failed;
use RC\Infrastructure\Logging\LogItem\ErrorMessage;
use RC\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use RC\Infrastructure\Logging\LogItem\InformationMessage;
use RC\Infrastructure\Logging\LogItem\InformationMessageWithData;
use RC\Infrastructure\Logging\Logs;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use RC\Infrastructure\UserStory\Body\Emptie;
use RC\Infrastructure\UserStory\Existent;
use RC\Infrastructure\UserStory\Response;
use RC\Infrastructure\UserStory\Response\RetryableServerError;
use RC\Infrastructure\UserStory\Response\Successful;
use RC\Infrastructure\Uuid\FromString as UuidFromString;

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

        $currentRound = new ByBotIdAndStartDateTime($this->botId, new Now(), $this->connection);
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

        // send matches to participants
        array_map(
            function (array $participant) {
                $toTelegramId = $participant['to'];
                $matchTelegramId = $participant['match'];
                $matchedInterests = $participant['common_interests'];
                $text = "Привет, %s!\nВаш собеседник -- %s, его ник в телеграме -- %s. У вас совпали такие интересы: %s. Вот что %s написал о себе: «%s».\n\nПриятного общения!";
            },
            (new Selecting(
                <<<q
select 
from meeting_round_pair pair
    join meeting_round_participant participant on pair.meeting_round_id = mr.id
    join "telegram_user" u on mri.user_id = u.id
    join bot b on b.id = mr.bot_id
where mr.bot_id = ? and status != ? and mr.invitation_date <= now() + interval '1 minute'
limit 100
q
                ,
                [$this->botId->value(), false],
                $this->connection
            ))
                ->response()->pure()->raw()
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
}