<?php

declare(strict_types=1);

namespace RC\Activities\User\RegistersInBot\UserStories\Domain\Reply;

use Meringue\Timeline\Point\Now;
use RC\Activities\User\RegistersInBot\UserStories\Domain\BotUser\RegisteredIfNoMoreQuestionsLeft;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\MeetingRound\ReadModel\LatestNotYetStartedWithFiveMinutesGap;
use RC\Domain\MeetingRound\ReadModel\MeetingRound;
use RC\Domain\BotUser\UserStatus\Impure\FromReadModelBotUser;
use RC\Domain\BotUser\UserStatus\Impure\FromPure;
use RC\Domain\BotUser\UserStatus\Pure\Registered;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Domain\SentReplyToUser\SentReplyToUser;
use RC\Infrastructure\TelegramBot\UserId\Pure\InternalTelegramUserId;
use RC\Activities\User\RegistersInBot\Domain\Reply\NextRegistrationQuestionReplyToUser;

class NextReplyToUser implements SentReplyToUser
{
    private $telegramUserId;
    private $botId;
    private $httpTransport;
    private $connection;

    public function __construct(InternalTelegramUserId $telegramUserId, BotId $botId, HttpTransport $httpTransport, OpenConnection $connection)
    {
        $this->telegramUserId = $telegramUserId;
        $this->botId = $botId;
        $this->httpTransport = $httpTransport;
        $this->connection = $connection;
    }

    public function value(): ImpureValue
    {
        if ($this->userRegistered()) {
            $latestMeetingRound = new LatestNotYetStartedWithFiveMinutesGap($this->botId, new Now(), $this->connection);
            if (!$latestMeetingRound->value()->isSuccessful()) {
                return $latestMeetingRound->value();
            }
            if ($latestMeetingRound->value()->pure()->isPresent()) {
                return $this->meetingRoundInvitation($latestMeetingRound);
            } else {
                return $this->congratulations();
            }
        } else {
            return
                (new NextRegistrationQuestionReplyToUser(
                    $this->telegramUserId,
                    $this->botId,
                    $this->connection,
                    $this->httpTransport
                ))
                    ->value();
        }
    }

    private function meetingRoundInvitation(MeetingRound $meetingRound)
    {
        return
            (new MeetingRoundInvitation(
                $meetingRound,
                $this->telegramUserId,
                $this->botId,
                $this->connection,
                $this->httpTransport
            ))
                ->value();
    }

    private function congratulations()
    {
        return
            (new RegistrationCongratulations(
                $this->telegramUserId,
                $this->botId,
                $this->connection,
                $this->httpTransport
            ))
                ->value();
    }

    private function userRegistered()
    {
        return
            (new FromReadModelBotUser(
                new RegisteredIfNoMoreQuestionsLeft(
                    $this->telegramUserId,
                    $this->botId,
                    $this->connection
                )
            ))
                ->equals(
                    new FromPure(new Registered())
                );
    }
}