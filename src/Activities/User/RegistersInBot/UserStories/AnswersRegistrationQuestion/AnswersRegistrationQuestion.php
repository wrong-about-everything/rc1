<?php

declare(strict_types=1);

namespace RC\Activities\User\RegistersInBot\UserStories\AnswersRegistrationQuestion;

use RC\Domain\Bot\BotId\FromUuid;
use RC\Domain\RegistrationQuestion\NextRegistrationQuestion;
use RC\Activities\User\RegistersInBot\UserStories\AnswersRegistrationQuestion\Domain\Reply\NextReplyToUser;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use RC\Infrastructure\Logging\LogItem\InformationMessage;
use RC\Infrastructure\Logging\Logs;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Domain\Bot\BotToken\Impure\ByBotId;
use RC\Domain\TelegramBot\Reply\Sorry;
use RC\Infrastructure\TelegramBot\UserId\Pure\FromParsedTelegramMessage;
use RC\Infrastructure\TelegramBot\UserMessage\Pure\FromParsedTelegramMessage as UserMessage;
use RC\Infrastructure\UserStory\Body\Emptie;
use RC\Infrastructure\UserStory\Existent;
use RC\Infrastructure\UserStory\Response;
use RC\Infrastructure\UserStory\Response\Successful;
use RC\Infrastructure\Uuid\FromString as UuidFromString;
use RC\Activities\User\RegistersInBot\UserStories\AnswersRegistrationQuestion\Domain\UserMessage\SavedAnswerToRegistrationQuestion;

class AnswersRegistrationQuestion extends Existent
{
    private $message;
    private $botId;
    private $httpTransport;
    private $connection;
    private $logs;

    public function __construct(array $message, string $botId, HttpTransport $httpTransport, OpenConnection $connection, Logs $logs)
    {
        $this->message = $message;
        $this->botId = $botId;
        $this->httpTransport = $httpTransport;
        $this->connection = $connection;
        $this->logs = $logs;
    }

    public function response(): Response
    {
        $this->logs->receive(new InformationMessage('User answers registration question scenario started.'));

        $savedAnswerValue =
            (new SavedAnswerToRegistrationQuestion(
                new FromParsedTelegramMessage($this->message),
                new FromUuid(new UuidFromString($this->botId)),
                new UserMessage($this->message),
                new NextRegistrationQuestion(
                    new FromParsedTelegramMessage($this->message),
                    new FromUuid(new UuidFromString($this->botId)),
                    $this->connection
                ),
                $this->connection
            ))
                ->value();
        if (!$savedAnswerValue->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($savedAnswerValue));
            $this->sorry()->value();
            return new Successful(new Emptie());
        }

        $nextReply = $this->nextReplyToUser()->value();
        if (!$nextReply->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($nextReply));
            $this->sorry()->value();
            return new Successful(new Emptie());
        }

        $this->logs->receive(new InformationMessage('User answers registration question scenario finished.'));

        return new Successful(new Emptie());
    }

    private function sorry()
    {
        return
            new Sorry(
                new FromParsedTelegramMessage($this->message),
                new ByBotId(
                    new FromUuid(new UuidFromString($this->botId)),
                    $this->connection
                ),
                $this->httpTransport
            );
    }

    private function nextReplyToUser()
    {
        return
            new NextReplyToUser(
                new FromParsedTelegramMessage($this->message),
                new FromUuid(new UuidFromString($this->botId)),
                $this->httpTransport,
                $this->connection
            );
    }
}