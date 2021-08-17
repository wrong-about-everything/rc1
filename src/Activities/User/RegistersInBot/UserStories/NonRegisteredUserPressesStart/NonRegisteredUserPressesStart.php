<?php

declare(strict_types=1);

namespace RC\Activities\User\RegistersInBot\UserStories\NonRegisteredUserPressesStart;

use RC\Activities\User\RegistersInBot\UserStories\Domain\Reply\NextReplyToUserToUser;
use RC\Domain\Bot\BotId\FromUuid;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use RC\Infrastructure\Logging\LogItem\InformationMessage;
use RC\Infrastructure\Logging\Logs;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Domain\Bot\BotToken\Impure\ByBotId;
use RC\Domain\TelegramBot\Reply\Text\Sorry;
use RC\Infrastructure\TelegramBot\UserId\Pure\FromParsedTelegramMessage;
use RC\Infrastructure\UserStory\Body\Emptie;
use RC\Infrastructure\UserStory\Existent;
use RC\Infrastructure\UserStory\Response;
use RC\Infrastructure\UserStory\Response\Successful;
use RC\Infrastructure\Uuid\FromString as UuidFromString;

class NonRegisteredUserPressesStart extends Existent
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
        $this->logs->receive(new InformationMessage('User presses start during registration scenario started'));

        $registrationStepValue = $this->nextReply()->value();
        if (!$registrationStepValue->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($registrationStepValue));
            $this->sorry()->value();
        }

        $this->logs->receive(new InformationMessage('User presses start during registration scenario finished'));

        return new Successful(new Emptie());
    }

    private function nextReply()
    {
        return
            new NextReplyToUserToUser(
                new FromParsedTelegramMessage($this->message),
                new FromUuid(new UuidFromString($this->botId)),
                $this->httpTransport,
                $this->connection
            );
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
}