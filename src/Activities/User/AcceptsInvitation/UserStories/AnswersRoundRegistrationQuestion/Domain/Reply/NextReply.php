<?php

declare(strict_types=1);

namespace RC\Activities\User\AcceptsInvitation\UserStories\AnswersRoundRegistrationQuestion\Domain\Reply;

use RC\Domain\BotId\BotId;
use RC\Domain\UserStatus\Impure\FromBotUser;
use RC\Domain\UserStatus\Impure\FromPure;
use RC\Domain\UserStatus\Pure\Registered;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\TelegramBot\Reply\Reply;
use RC\Infrastructure\TelegramBot\UserId\Pure\TelegramUserId;
use RC\Activities\User\AcceptsInvitation\Domain\Reply\NextRoundRegistrationQuestionReply;

class NextReply implements Reply
{
    private $telegramUserId;
    private $botId;
    private $httpTransport;
    private $connection;

    public function __construct(TelegramUserId $telegramUserId, BotId $botId, HttpTransport $httpTransport, OpenConnection $connection)
    {
        $this->telegramUserId = $telegramUserId;
        $this->botId = $botId;
        $this->httpTransport = $httpTransport;
        $this->connection = $connection;
    }

    public function value(): ImpureValue
    {
        if ($this->noMoreQuestionsLeft()) {
            return $this->congratulations();
        } else {
            return
                (new NextRoundRegistrationQuestionReply(
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
            (new MeetingRoundInvitationCongratulations(
                $this->telegramUserId,
                $this->botId,
                $this->connection,
                $this->httpTransport
            ))
                ->value();
    }

    private function noMoreQuestionsLeft()
    {
        return
            (new FromBotUser(
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