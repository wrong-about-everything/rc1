<?php

declare(strict_types=1);

namespace RC\Activities\User\AcceptsInvitation\UserStories\AnswersRoundRegistrationQuestion;

use RC\Activities\User\AcceptsInvitation\UserStories\AnswersRoundRegistrationQuestion\Domain\Reply\NextReply;
use RC\Activities\User\AcceptsInvitation\UserStories\AnswersRoundRegistrationQuestion\Domain\ParticipantAnsweredToRoundRegistrationQuestion;
use RC\Domain\AnswerOptions\AnswerOptions;
use RC\Domain\AnswerOptions\FromRoundRegistrationQuestion as AnswerOptionsFromRoundRegistrationQuestion;
use RC\Domain\Bot\BotId\FromUuid;
use RC\Domain\RoundInvitation\InvitationId\Impure\FromInvitation;
use RC\Domain\RoundInvitation\InvitationId\Impure\InvitationId;
use RC\Domain\RoundInvitation\ReadModel\InvitationForTheLatestRoundByTelegramUserIdAndBotId;
use RC\Domain\RoundRegistrationQuestion\NextRoundRegistrationQuestion;
use RC\Domain\RoundRegistrationQuestion\RoundRegistrationQuestion;
use RC\Domain\RoundRegistrationQuestion\Type\Impure\FromPure;
use RC\Domain\RoundRegistrationQuestion\Type\Impure\FromRoundRegistrationQuestion;
use RC\Domain\RoundRegistrationQuestion\Type\Pure\NetworkingOrSomeSpecificArea;
use RC\Domain\TelegramBot\Reply\ValidationError;
use RC\Domain\UserInterest\InterestName\Pure\FromString;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use RC\Infrastructure\Logging\LogItem\InformationMessage;
use RC\Infrastructure\Logging\Logs;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Domain\Bot\BotToken\Impure\ByBotId;
use RC\Domain\TelegramBot\Reply\Sorry;
use RC\Infrastructure\TelegramBot\UserId\Pure\FromParsedTelegramMessage;
use RC\Infrastructure\TelegramBot\UserMessage\Pure\FromParsedTelegramMessage as UserReply;
use RC\Infrastructure\TelegramBot\UserMessage\Pure\UserMessage;
use RC\Infrastructure\UserStory\Body\Emptie;
use RC\Infrastructure\UserStory\Existent;
use RC\Infrastructure\UserStory\Response;
use RC\Infrastructure\UserStory\Response\Successful;
use RC\Infrastructure\Uuid\FromString as UuidFromString;

class AnswersRoundRegistrationQuestion extends Existent
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
        $this->logs->receive(new InformationMessage('User answers round invitation question scenario started.'));

        $invitationId = $this->invitationId();
        $currentlyAnsweredQuestion = new NextRoundRegistrationQuestion($invitationId, $this->connection);
        if ($this->isInvalid($currentlyAnsweredQuestion, new UserReply($this->message))) {
            $this->validationError(new AnswerOptionsFromRoundRegistrationQuestion($currentlyAnsweredQuestion, $invitationId, $this->connection))->value();
            return new Successful(new Emptie());
        }

        $participantValue = $this->participant($invitationId, $currentlyAnsweredQuestion)->value();
        if (!$participantValue->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($participantValue));
            $this->sorry()->value();
            return new Successful(new Emptie());
        }

        $nextReply = $this->nextReplyToUser($invitationId)->value();
        if (!$nextReply->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($nextReply));
            $this->sorry()->value();
            return new Successful(new Emptie());
        }

        $this->logs->receive(new InformationMessage('User answers round invitation question scenario started.'));

        return new Successful(new Emptie());
    }

    private function botId()
    {
        return new FromUuid(new UuidFromString($this->botId));
    }

    private function validationError(AnswerOptions $answerOptions)
    {
        return
            new ValidationError(
                $answerOptions,
                new FromParsedTelegramMessage($this->message),
                new ByBotId(
                    new FromUuid(new UuidFromString($this->botId)),
                    $this->connection
                ),
                $this->httpTransport
            );
    }

    private function isInvalid(RoundRegistrationQuestion $currentlyAnsweredQuestion, UserMessage $userReply): bool
    {
        return
            (
                (new FromRoundRegistrationQuestion($currentlyAnsweredQuestion))->equals(new FromPure(new NetworkingOrSomeSpecificArea()))
                &&
                !(new FromString($userReply->value()))->exists()
            );
    }

    private function invitationId()
    {
        return
            new FromInvitation(
                new InvitationForTheLatestRoundByTelegramUserIdAndBotId(
                    new FromParsedTelegramMessage($this->message),
                    new FromUuid(new UuidFromString($this->botId)),
                    $this->connection
                )
            );
    }

    private function participant(InvitationId $invitationId, RoundRegistrationQuestion $roundRegistrationQuestion)
    {
        return
            new ParticipantAnsweredToRoundRegistrationQuestion(
                new UserReply($this->message),
                $invitationId,
                $roundRegistrationQuestion,
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

    private function nextReplyToUser(InvitationId $invitationId)
    {
        return
            new NextReply(
                $invitationId,
                new FromParsedTelegramMessage($this->message),
                new FromUuid(new UuidFromString($this->botId)),
                $this->httpTransport,
                $this->connection
            );
    }
}