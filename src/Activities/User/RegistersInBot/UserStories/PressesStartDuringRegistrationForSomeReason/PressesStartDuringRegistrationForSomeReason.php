<?php

declare(strict_types=1);

namespace RC\Activities\User\RegistersInBot\UserStories\PressesStartDuringRegistrationForSomeReason;

use RC\Domain\Bot\BotId\FromUuid;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use RC\Infrastructure\Logging\LogItem\InformationMessage;
use RC\Infrastructure\Logging\Logs;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Domain\Bot\BotToken\Impure\ByBotId;
use RC\Domain\TelegramBot\Reply\Sorry;
use RC\Infrastructure\TelegramBot\UserId\Pure\FromParsedTelegramMessage;
use RC\Infrastructure\UserStory\Body\Emptie;
use RC\Infrastructure\UserStory\Existent;
use RC\Infrastructure\UserStory\Response;
use RC\Infrastructure\UserStory\Response\Successful;
use RC\Infrastructure\Uuid\FromString as UuidFromString;
use RC\Activities\User\RegistersInBot\Domain\Reply\NextRegistrationQuestionReply;

class PressesStartDuringRegistrationForSomeReason extends Existent
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
        $this->logs->receive(new InformationMessage('User presses start during registration for some reason scenario started'));

        $registrationStepValue = $this->registrationStep()->value();
        if (!$registrationStepValue->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($registrationStepValue));
            $this->sorry()->value();
        }

        $this->logs->receive(new InformationMessage('User presses start during registration for some reason scenario finished'));

        return new Successful(new Emptie());
    }

    private function registrationStep()
    {
        return
            new NextRegistrationQuestionReply(
                new FromParsedTelegramMessage($this->message),
                new FromUuid(new UuidFromString($this->botId)),
                $this->connection,
                $this->httpTransport
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