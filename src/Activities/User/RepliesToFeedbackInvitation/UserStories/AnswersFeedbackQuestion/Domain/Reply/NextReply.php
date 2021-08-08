<?php

declare(strict_types=1);

namespace RC\Activities\User\RepliesToFeedbackInvitation\UserStories\AnswersFeedbackQuestion\Domain\Reply;

use RC\Activities\User\RepliesToFeedbackInvitation\Domain\Reply\ThanksForFeedback;
use RC\Activities\User\RepliesToFeedbackInvitation\UserStories\AnswersFeedbackQuestion\Domain\Participant\RegisteredIfNoMoreQuestionsLeftOrHisInterestIsNetworking;
use RC\Activities\User\RepliesToFeedbackInvitation\Domain\Reply\NextFeedbackQuestionReply;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\MeetingRound\MeetingRoundId\Impure\FromInvitation;
use RC\Domain\MeetingRound\ReadModel\ById as MeetingRoundById;
use RC\Domain\Participant\ParticipantId\Impure\FromWriteModelParticipant;
use RC\Domain\Participant\ReadModel\ById;
use RC\Domain\Participant\Status\Impure\FromPure;
use RC\Domain\Participant\Status\Impure\FromReadModelParticipant;
use RC\Domain\Participant\Status\Pure\Registered;
use RC\Domain\RoundInvitation\InvitationId\Impure\InvitationId;
use RC\Domain\RoundInvitation\ReadModel\ByImpureId;
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

        if ($this->participantRegisteredForARound()) {
            return $this->congratulations();
        } else {
            return
                (new NextFeedbackQuestionReply(
                    $this->invitationId,
                    $this->telegramUserId,
                    $this->botId,
                    $this->connection,
                    $this->httpTransport
                ))
                    ->value();
        }
    }

    private function congratulations()
    {
        return
            (new ThanksForFeedback(
                $this->telegramUserId,
                $this->botId,
                new MeetingRoundById(new FromInvitation(new ByImpureId($this->invitationId, $this->connection)), $this->connection),
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
                        new RegisteredIfNoMoreQuestionsLeftOrHisInterestIsNetworking(
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