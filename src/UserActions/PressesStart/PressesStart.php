<?php

declare(strict_types=1);

namespace RC\UserActions\PressesStart;

use RC\Domain\ReplyToUser\Text\FillInYourUserNameAndFirstName;
use RC\Domain\ReplyToUser\Text\InCaseOfAnyUncertainty;
use RC\Domain\BotUser\UserStatus\Impure\FromBotUser;
use RC\Domain\BotUser\UserStatus\Impure\FromPure as ImpureUserStatusFromPure;
use RC\Domain\BotUser\UserStatus\Impure\UserStatus;
use RC\Domain\BotUser\UserStatus\Pure\Registered;
use RC\Domain\BotUser\UserStatus\Pure\RegistrationIsInProgress;
use RC\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Domain\Bot\BotId\FromUuid;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\Logging\LogItem\InformationMessage;
use RC\Infrastructure\Logging\Logs;
use RC\Domain\Bot\BotToken\Impure\ByBotId;
use RC\Domain\ReplyToUser\Text\Sorry;
use RC\Domain\BotUser\AddedIfNotYet;
use RC\Infrastructure\TelegramBot\UserId\Pure\FromParsedTelegramMessage;
use RC\Infrastructure\UserStory\Body\Emptie;
use RC\Infrastructure\UserStory\Existent;
use RC\Infrastructure\UserStory\Response;
use RC\Infrastructure\UserStory\Response\Successful;
use RC\Infrastructure\Uuid\FromString as UuidFromString;
use RC\Activities\User\RegistersInBot\UserStories\NonRegisteredUserPressesStart\NonRegisteredUserPressesStart;

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

        if ($this->eitherUsernameOrFirstNameIsEmpty()) {
            $this->fillInYourUsernameAndFirstName()->value();
            return new Successful(new Emptie());
        }

        $userStatus = $this->userStatus();
        if (!$userStatus->value()->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($userStatus->value()));
            $this->sorry()->value();
            return new Successful(new Emptie());
        }

        if ($userStatus->equals(new ImpureUserStatusFromPure(new RegistrationIsInProgress()))) {
            return
                (new NonRegisteredUserPressesStart(
                    $this->message,
                    $this->botId,
                    $this->httpTransport,
                    $this->connection,
                    $this->logs
                ))
                    ->response();
        } elseif ($userStatus->equals(new ImpureUserStatusFromPure(new Registered()))) {
            $userIsAlreadyRegisteredValue = $this->replyInCaseOfAnyUncertainty()->value();
            if (!$userIsAlreadyRegisteredValue->isSuccessful()) {
                $this->logs->receive(new FromNonSuccessfulImpureValue($userIsAlreadyRegisteredValue));
                $this->sorry()->value();
                return new Successful(new Emptie());
            }
        }

        $this->logs->receive(new InformationMessage('User presses start scenario finished'));

        return new Successful(new Emptie());
    }

    private function eitherUsernameOrFirstNameIsEmpty(): bool
    {
        return
            !isset($this->message['message']['from']['first_name'])
                ||
            empty($this->message['message']['from']['first_name'])
                ||
            !isset($this->message['message']['from']['username'])
                ||
            empty($this->message['message']['from']['username']);
    }

    private function userStatus(): UserStatus
    {
        return
            new FromBotUser(
                new AddedIfNotYet(
                    new FromParsedTelegramMessage($this->message),
                    new FromUuid(new UuidFromString($this->botId)),
                    $this->message['message']['from']['first_name'],
                    $this->message['message']['from']['last_name'] ?? '',
                    $this->message['message']['from']['username'],
                    $this->connection
                )
            );
    }

    private function replyInCaseOfAnyUncertainty()
    {
        return
            new InCaseOfAnyUncertainty(
                new FromParsedTelegramMessage($this->message),
                new FromUuid(new UuidFromString($this->botId)),
                $this->connection,
                $this->httpTransport
            );
    }

    private function fillInYourUsernameAndFirstName()
    {
        return
            new FillInYourUserNameAndFirstName(
                new FromParsedTelegramMessage($this->message),
                new ByBotId(
                    new FromUuid(new UuidFromString($this->botId)),
                    $this->connection
                ),
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