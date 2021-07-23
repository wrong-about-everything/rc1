<?php

declare(strict_types=1);

namespace RC\UserActions\SendsArbitraryMessage;

use RC\Domain\BotUser\ByTelegramUserId;
use RC\Domain\MeetingRoundInvitation\LatestByTelegramUserIdAndBotId;
use RC\Domain\MeetingRoundInvitation\Status\Impure\FromInvitation;
use RC\Domain\MeetingRoundInvitation\Status\Impure\FromPure;
use RC\Domain\MeetingRoundInvitation\Status\Pure\Sent;
use RC\Domain\TelegramBot\Reply\InCaseOfAnyUncertainty;
use RC\Domain\UserStatus\Impure\FromBotUser;
use RC\Domain\UserStatus\Impure\FromPure as ImpureUserStatusFromPure;
use RC\Domain\UserStatus\Pure\Registered;
use RC\Domain\UserStatus\Pure\RegistrationIsInProgress;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful as SuccessfulValue;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie as EmptieValue;
use RC\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Domain\BotId\FromUuid;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\Logging\LogItem\InformationMessage;
use RC\Infrastructure\Logging\Logs;
use RC\Infrastructure\TelegramBot\BotToken\Impure\ByBotId;
use RC\Infrastructure\TelegramBot\Reply\Sorry;
use RC\Infrastructure\TelegramBot\UserId\Pure\FromParsedTelegramMessage;
use RC\Infrastructure\TelegramBot\UserMessage\FromParsedTelegramMessage as UserMessage;
use RC\Infrastructure\UserStory\Body\Emptie;
use RC\Infrastructure\UserStory\Existent;
use RC\Infrastructure\UserStory\Response;
use RC\Infrastructure\UserStory\Response\Successful;
use RC\Infrastructure\Uuid\FromString as UuidFromString;
use RC\Activities\User\RegistersInBot\UserStories\AnswersRegistrationQuestion\AnswersRegistrationQuestion;
use RC\Activities\User\AnswersInvitationQuestion\AnswersInvitationQuestion;

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

        $userStatus = $this->userStatus();
        if (!$userStatus->value()->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($userStatus->value()));
            $this->sorry()->value();
            return new Successful(new Emptie());
        }

        if ($userStatus->equals(new ImpureUserStatusFromPure(new RegistrationIsInProgress()))) {
            return $this->answersRegistrationQuestion();
        } elseif ($userStatus->equals(new ImpureUserStatusFromPure(new Registered())) && $this->thereIsAPendingInvitation()) {
            return $this->answersInvitationQuestion();
        } else {
            $userIsAlreadyRegisteredValue = $this->replyInCaseOfAnyUncertainty()->value();
            if (!$userIsAlreadyRegisteredValue->isSuccessful()) {
                $this->logs->receive(new FromNonSuccessfulImpureValue($userIsAlreadyRegisteredValue));
                $this->sorry()->value();
                return new Successful(new Emptie());
            }
        }

        $this->logs->receive(new InformationMessage('User sends arbitrary message scenario finished'));

        return new Successful(new Emptie());
    }

    private function userStatus()
    {
        return
            new FromBotUser(
                new ByTelegramUserId(
                    new FromParsedTelegramMessage($this->message),
                    new FromUuid(new UuidFromString($this->botId)),
                    $this->connection
                )
            );
    }

    private function answersRegistrationQuestion()
    {
        return
            (new AnswersRegistrationQuestion(
                $this->message,
                $this->botId,
                $this->httpTransport,
                $this->connection,
                $this->logs
            ))
                ->response();
    }

    private function answersInvitationQuestion()
    {
        return
            (new AnswersInvitationQuestion(
                $this->message,
                $this->botId,
                $this->httpTransport,
                $this->connection,
                $this->logs
            ))
                ->response();
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

    private function thereIsAPendingInvitation()
    {
        $latestInvitationStatus =
            new FromInvitation(
                new LatestByTelegramUserIdAndBotId(
                    new FromParsedTelegramMessage($this->message),
                    new FromUuid(new UuidFromString($this->botId)),
                    $this->connection
                )
            );
        if (!$latestInvitationStatus->exists()->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($latestInvitationStatus->value()));
            return false;
        }
        if ($latestInvitationStatus->exists()->pure()->raw() === false) {
            return false;
        }

        return $latestInvitationStatus->equals(new FromPure(new Sent()));
    }
}