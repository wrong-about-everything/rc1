<?php

declare(strict_types=1);

namespace RC\Activities\User\AcceptsInvitation\UserStories\RepliesToRoundInvitation\Domain\Reply;

use RC\Activities\User\AcceptsInvitation\Domain\Reply\NextRoundRegistrationQuestionReply;
use RC\Activities\User\AcceptsInvitation\UserStories\AnswersRoundRegistrationQuestion\Domain\Reply\RoundRegistrationCongratulations;
use RC\Activities\User\AcceptsInvitation\UserStories\RepliesToRoundInvitation\Domain\Invitation\UserRegisteredIfNoMoreQuestionsLeft;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\Bot\BotToken\Impure\ByBotId;
use RC\Domain\RoundInvitation\InvitationId\Impure\FromWriteModelInvitation;
use RC\Domain\RoundInvitation\InvitationId\Impure\InvitationId;
use RC\Domain\RoundInvitation\InvitationId\Pure\FromImpure;
use RC\Domain\RoundInvitation\ReadModel\ById;
use RC\Domain\RoundInvitation\ReadModel\ByImpureId;
use RC\Domain\RoundInvitation\Status\Impure\FromInvitation;
use RC\Domain\RoundInvitation\Status\Impure\FromPure;
use RC\Domain\RoundInvitation\Status\Impure\FromPure as ImpureStatusFromPure;
use RC\Domain\RoundInvitation\Status\Pure\Declined;
use RC\Domain\RoundInvitation\Status\Pure\UserRegistered;
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

        if ((new FromInvitation(new ById(new FromImpure($this->invitationId), $this->connection)))->equals(new ImpureStatusFromPure(new Declined()))) {
            return $this->seeYouNextTime();
        } elseif ($this->userRegisteredForARound()) {
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
                $this->connection,
                $this->httpTransport
            ))
                ->value();
    }

    private function userRegisteredForARound()
    {
        return
            (new FromInvitation(
                new ByImpureId(
                    new FromWriteModelInvitation(
                        new UserRegisteredIfNoMoreQuestionsLeft(
                            $this->invitationId,
                            $this->connection
                        )
                    ),
                    $this->connection
                )
            ))
                ->equals(
                    new FromPure(new UserRegistered())
                );
    }
}