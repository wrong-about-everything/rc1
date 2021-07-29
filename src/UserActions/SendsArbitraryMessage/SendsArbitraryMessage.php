<?php

declare(strict_types=1);

namespace RC\UserActions\SendsArbitraryMessage;

use RC\Activities\User\AcceptsInvitation\UserStories\AnswersRoundRegistrationQuestion\AnswersRoundRegistrationQuestion;
use RC\Activities\User\AcceptsInvitation\UserStories\RepliesToRoundInvitation\RepliesToRoundInvitation;
use RC\Domain\BotUser\ByTelegramUserId;
use RC\Domain\Participant\ReadModel\ByInvitationId;
use RC\Domain\Participant\Status\Impure\FromPure as ParticipantStatus;
use RC\Domain\Participant\Status\Impure\FromReadModelParticipant;
use RC\Domain\Participant\Status\Pure\RegistrationInProgress;
use RC\Domain\RoundInvitation\InvitationId\Impure\FromInvitation as InvitationId;
use RC\Domain\RoundInvitation\ReadModel\InvitationForTheLatestRoundByTelegramUserIdAndBotId;
use RC\Domain\RoundInvitation\Status\Impure\FromInvitation;
use RC\Domain\RoundInvitation\Status\Impure\FromPure;
use RC\Domain\RoundInvitation\Status\Pure\Sent;
use RC\Domain\TelegramBot\Reply\InCaseOfAnyUncertainty;
use RC\Domain\User\UserStatus\Impure\FromBotUser;
use RC\Domain\User\UserStatus\Impure\FromPure as ImpureUserStatusFromPure;
use RC\Domain\User\UserStatus\Pure\Registered;
use RC\Domain\User\UserStatus\Pure\RegistrationIsInProgress;
use RC\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Domain\Bot\BotId\FromUuid;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\Logging\LogItem\InformationMessage;
use RC\Infrastructure\Logging\Logs;
use RC\Domain\Bot\BotToken\Impure\ByBotId;
use RC\Domain\TelegramBot\Reply\Sorry;
use RC\Infrastructure\TelegramBot\UserId\Pure\FromParsedTelegramMessage;
use RC\Infrastructure\UserStory\Body\Emptie;
use RC\Infrastructure\UserStory\Existent;
use RC\Infrastructure\UserStory\Response;
use RC\Infrastructure\UserStory\Response\Successful;
use RC\Infrastructure\Uuid\FromString as UuidFromString;
use RC\Activities\User\RegistersInBot\UserStories\AnswersRegistrationQuestion\AnswersRegistrationQuestion;

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
            return $this->repliesToRoundInvitation();
        } elseif ($userStatus->equals(new ImpureUserStatusFromPure(new Registered())) && $this->thereIsAUserRegisteringForARound()) {
            return $this->answersRoundRegistrationQuestion();
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

    private function answersRoundRegistrationQuestion()
    {
        return
            (new AnswersRoundRegistrationQuestion(
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
                new InvitationForTheLatestRoundByTelegramUserIdAndBotId(
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

    private function repliesToRoundInvitation()
    {
        return
            (new RepliesToRoundInvitation(
                $this->message,
                $this->botId,
                $this->httpTransport,
                $this->connection,
                $this->logs
            ))
                ->response();
    }

    private function thereIsAUserRegisteringForARound()
    {
        $participant =
            new ByInvitationId(
                new InvitationId(
                    new InvitationForTheLatestRoundByTelegramUserIdAndBotId(
                        new FromParsedTelegramMessage($this->message),
                        new FromUuid(new UuidFromString($this->botId)),
                        $this->connection
                    )
                ),
                $this->connection
            );
        if (!$participant->exists()->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($participant->value()));
            return false;
        }
        if ($participant->exists()->pure()->raw() === false) {
            return false;
        }

        return (new FromReadModelParticipant($participant))->equals(new ParticipantStatus(new RegistrationInProgress()));
    }
}