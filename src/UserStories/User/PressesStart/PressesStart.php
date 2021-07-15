<?php

declare(strict_types=1);

namespace RC\UserStories\User\PressesStart;

use RC\Domain\TelegramBot\Reply\ActualRegistrationStep;
use RC\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\TelegramBot\BotId\FromString;
use RC\Infrastructure\TelegramBot\ChatId\FromMessage;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\Logging\LogItem\InformationMessage;
use RC\Infrastructure\Logging\Logs;
use RC\Infrastructure\TelegramBot\UserId\FromParsedTelegramMessage;
use RC\Infrastructure\UserStory\Body\Emptie;
use RC\Infrastructure\UserStory\Existent;
use RC\Infrastructure\UserStory\Response;
use RC\Infrastructure\UserStory\Response\Successful;
use RC\Infrastructure\Uuid\FromString as UuidFromString;

class PressesStart extends Existent
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
        $this->logs->receive(new InformationMessage('User presses start scenario started'));

        $reply =
            (new ActualRegistrationStep(
                new FromParsedTelegramMessage($this->message),
                new FromString(new UuidFromString($this->botId)),
                new FromMessage($this->message),
                $this->connection,
                $this->httpTransport
            ))
                ->value();
        if (!$reply->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($reply));
        }
        $this->logs->receive(new InformationMessage('User presses start scenario finished'));

        return new Successful(new Emptie());
    }
}