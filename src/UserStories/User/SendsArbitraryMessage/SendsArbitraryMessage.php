<?php

declare(strict_types=1);

namespace RC\UserStories\User\SendsArbitraryMessage;

use RC\Domain\RegistrationQuestion\CurrentRegistrationQuestion;
use RC\Domain\TelegramBot\Reply\ActualRegistrationStep;
use RC\Domain\TelegramBot\UserMessage\SavedAnswerToQuestion;
use RC\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Domain\BotId\FromUuid;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\Logging\LogItem\InformationMessage;
use RC\Infrastructure\Logging\Logs;
use RC\Infrastructure\TelegramBot\BotToken\ByBotId;
use RC\Infrastructure\TelegramBot\Reply\Sorry;
use RC\Infrastructure\TelegramBot\UserId\Impure\FromPure;
use RC\Infrastructure\TelegramBot\UserId\Pure\FromParsedTelegramMessage;
use RC\Infrastructure\TelegramBot\UserMessage\FromParsedTelegramMessage as UserMessage;
use RC\Infrastructure\UserStory\Body\Emptie;
use RC\Infrastructure\UserStory\Existent;
use RC\Infrastructure\UserStory\Response;
use RC\Infrastructure\UserStory\Response\Successful;
use RC\Infrastructure\Uuid\FromString as UuidFromString;

class SendsArbitraryMessage extends Existent
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
        $this->logs->receive(new InformationMessage('User sends arbitrary message scenario started'));

        $savedAnswerValue =
            (new SavedAnswerToQuestion(
                new FromParsedTelegramMessage($this->message),
                new FromUuid(new UuidFromString($this->botId)),
                new UserMessage($this->message),
                new CurrentRegistrationQuestion(
                    new FromParsedTelegramMessage($this->message),
                    new FromUuid(new UuidFromString($this->botId)),
                    $this->connection
                ),
                $this->connection
            ))
                ->value();
        if (!$savedAnswerValue->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($savedAnswerValue));
            $this->sorry();
        }

        $reply =
            (new ActualRegistrationStep(
                new FromPure(new FromParsedTelegramMessage($this->message)),
                new FromUuid(new UuidFromString($this->botId)),
                $this->connection,
                $this->httpTransport
            ))
                ->value();
        if (!$reply->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($reply));
            $this->sorry();
        }

        $this->logs->receive(new InformationMessage('User sends arbitrary message scenario finished'));

        return new Successful(new Emptie());
    }

    private function sorry()
    {
        $sorryValue =
            (new Sorry(
                new FromParsedTelegramMessage($this->message),
                new ByBotId(
                    new FromUuid(new UuidFromString($this->botId)),
                    $this->connection
                ),
                $this->httpTransport
            ))
                ->value();
        if (!$sorryValue->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($sorryValue));
        }
    }
}