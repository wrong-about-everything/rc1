<?php

declare(strict_types=1);

namespace RC\Activities\User\RegistersInBot\UserStories\AnswersRegistrationQuestion\Domain\Reply;

use RC\Domain\Bot\BotId\BotId;
use RC\Domain\User\UserStatus\Impure\FromBotUser;
use RC\Domain\User\UserStatus\Impure\FromPure;
use RC\Domain\User\UserStatus\Pure\Registered;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Domain\TelegramBot\Reply\Reply;
use RC\Infrastructure\TelegramBot\UserId\Pure\TelegramUserId;
use RC\Activities\User\RegistersInBot\Domain\Reply\NextRegistrationQuestionReply;

class NextReplyToUser implements Reply
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
                (new NextRegistrationQuestionReply(
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
            (new RegistrationCongratulations(
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