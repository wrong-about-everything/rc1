<?php

declare(strict_types=1);

namespace RC\Activities\User\RepliesToRoundInvitation\UserStories\AcceptsOrDeclinesInvitation\Domain\Reply;

use RC\Activities\User\RepliesToRoundInvitation\Domain\Reply\NextRoundRegistrationQuestionReply;
use RC\Activities\User\RepliesToRoundInvitation\Domain\Reply\RoundRegistrationCongratulations;
use RC\Activities\User\RepliesToRoundInvitation\UserStories\AcceptsOrDeclinesInvitation\Domain\Participant\RegisteredIfNoMoreQuestionsLeft;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\Bot\BotToken\Impure\ByBotId;
use RC\Domain\MeetingRound\MeetingRoundId\Impure\FromInvitation as MeetingRoundIdFromInvitation;
use RC\Domain\MeetingRound\ReadModel\ById as MeetingRoundById;
use RC\Domain\Participant\ParticipantId\Impure\FromWriteModelParticipant;
use RC\Domain\Participant\ReadModel\ById;
use RC\Domain\Participant\Status\Impure\FromPure;
use RC\Domain\Participant\Status\Impure\FromReadModelParticipant;
use RC\Domain\Participant\Status\Pure\Registered;
use RC\Domain\RoundInvitation\InvitationId\Impure\InvitationId;
use RC\Domain\RoundInvitation\InvitationId\Pure\FromImpure;
use RC\Domain\RoundInvitation\ReadModel\ById as InvitationById;
use RC\Domain\RoundInvitation\ReadModel\ByImpureId;
use RC\Domain\RoundInvitation\Status\Impure\FromInvitation;
use RC\Domain\RoundInvitation\Status\Impure\FromPure as ImpureInvitationStatusFromPure;
use RC\Domain\RoundInvitation\Status\Pure\Declined;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Domain\TelegramBot\Reply\Reply;
use RC\Infrastructure\TelegramBot\UserId\Pure\TelegramUserId;

class NextReply implements Reply
{
    private $invitationId;
    private $telegramUserId;
    private $botId;
    private $httpTransport;
    private $connection;

    public function __construct(InvitationId $invitationId, TelegramUserId $telegramUserId, BotId $botId, HttpTransport $httpTransport, OpenConnection $connection)
    {
        $this->invitationId = $invitationId;
        $this->telegramUserId = $telegramUserId;
        $this->botId = $botId;
        $this->httpTransport = $httpTransport;
        $this->connection = $connection;
    }

    public function value(): ImpureValue
    {
        if (!$this->invitationId->value()->isSuccessful()) {
            return $this->invitationId->value();
        }

        if ((new FromInvitation(new InvitationById(new FromImpure($this->invitationId), $this->connection)))->equals(new ImpureInvitationStatusFromPure(new Declined()))) {
            return $this->seeYouNextTime();
        } elseif ($this->participantRegisteredForARound()) {
            return $this->congratulations();
        } else {
            return
                (new NextRoundRegistrationQuestionReply(
                    $this->invitationId,
                    $this->telegramUserId,
                    $this->botId,
                    $this->connection,
                    $this->httpTransport
                ))
                    ->value();
        }
    }

    private function seeYouNextTime()
    {
        return
            (new InvitationDeclinedAndSeeYouNextTime(
                $this->telegramUserId,
                new ByBotId($this->botId, $this->connection),
                $this->httpTransport
            ))
                ->value();
    }

    private function congratulations()
    {
        return
            (new RoundRegistrationCongratulations(
                $this->telegramUserId,
                $this->botId,
                new MeetingRoundById(new MeetingRoundIdFromInvitation(new ByImpureId($this->invitationId, $this->connection)), $this->connection),
                $this->connection,
                $this->httpTransport
            ))
                ->value();
    }

    private function participantRegisteredForARound()
    {
        return
            (new FromReadModelParticipant(
                new ById(
                    new FromWriteModelParticipant(
                        new RegisteredIfNoMoreQuestionsLeft(
                            $this->invitationId,
                            $this->connection
                        )
                    ),
                    $this->connection
                )
            ))
                ->equals(
                    new FromPure(new Registered())
                );
    }
}