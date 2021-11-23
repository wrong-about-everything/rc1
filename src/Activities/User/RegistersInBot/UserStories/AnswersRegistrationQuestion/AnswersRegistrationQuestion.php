<?php

declare(strict_types=1);

namespace RC\Activities\User\RegistersInBot\UserStories\AnswersRegistrationQuestion;

use RC\Domain\Bot\ById;
use RC\Domain\SentReplyToUser\ReplyOptions\ReplyOptions;
use RC\Domain\SentReplyToUser\ReplyOptions\FromRegistrationQuestion as AnswerOptionsFromRegistrationQuestion;
use RC\Domain\Bot\BotId\FromUuid;
use RC\Domain\Experience\ExperienceName\FromString;
use RC\Domain\Position\PositionName\FromString as PositionNameFromString;
use RC\Domain\RegistrationQuestion\NextRegistrationQuestion;
use RC\Activities\User\RegistersInBot\UserStories\Domain\Reply\NextReplyToUser;
use RC\Domain\RegistrationQuestion\RegistrationQuestion;
use RC\Domain\SentReplyToUser\ValidationError;
use RC\Domain\RegistrationQuestion\RegistrationQuestionType\Impure\FromPure;
use RC\Domain\RegistrationQuestion\RegistrationQuestionType\Impure\FromRegistrationQuestion;
use RC\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\Experience;
use RC\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\Position;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use RC\Infrastructure\Logging\LogItem\InformationMessage;
use RC\Infrastructure\Logging\Logs;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Domain\Bot\BotToken\Impure\ByBotId;
use RC\Domain\SentReplyToUser\Sorry;
use RC\Infrastructure\TelegramBot\UserId\Pure\FromParsedTelegramMessage;
use RC\Infrastructure\TelegramBot\UserMessage\Pure\FromParsedTelegramMessage as UserReply;
use RC\Infrastructure\TelegramBot\UserMessage\Pure\UserMessage;
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

        $currentlyAnsweredQuestion = $this->currentlyAnsweredQuestion();
        if ($this->isInvalid($currentlyAnsweredQuestion, new UserReply($this->message))) {
            $this->validationError(new AnswerOptionsFromRegistrationQuestion($currentlyAnsweredQuestion, $this->botId(), $this->connection))->value();
            return new Successful(new Emptie());
        }

        $savedAnswerValue = $this->savedAnswer($currentlyAnsweredQuestion)->value();
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

    private function botId()
    {
        return new FromUuid(new UuidFromString($this->botId));
    }

    private function currentlyAnsweredQuestion()
    {
        return
            new NextRegistrationQuestion(
                new FromParsedTelegramMessage($this->message),
                new FromUuid(new UuidFromString($this->botId)),
                $this->connection
            );
    }

    private function validationError(ReplyOptions $answerOptions)
    {
        return
            new ValidationError(
                $answerOptions,
                new FromParsedTelegramMessage($this->message),
                new ById(
                    new FromUuid(new UuidFromString($this->botId)),
                    $this->connection
                ),
                $this->httpTransport
            );
    }

    private function isInvalid(RegistrationQuestion $currentlyAnsweredQuestion, UserMessage $userReply): bool
    {
        return
            (
                (new FromRegistrationQuestion($currentlyAnsweredQuestion))->equals(new FromPure(new Position()))
                    &&
                !(new PositionNameFromString($userReply->value()))->exists()
            )
                ||
            (
                (new FromRegistrationQuestion($currentlyAnsweredQuestion))->equals(new FromPure(new Experience()))
                    &&
                !(new FromString($userReply->value()))->exists()
            );
    }

    private function savedAnswer(RegistrationQuestion $currentlyAnsweredQuestion)
    {
        return
            new SavedAnswerToRegistrationQuestion(
                new FromParsedTelegramMessage($this->message),
                new FromUuid(new UuidFromString($this->botId)),
                new UserReply($this->message),
                $currentlyAnsweredQuestion,
                $this->connection
            );
    }

    private function sorry()
    {
        return
            new Sorry(
                new FromParsedTelegramMessage($this->message),
                new ById(
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