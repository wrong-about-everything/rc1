<?php

declare(strict_types=1);

namespace RC\Activities\User\RepliesToFeedbackInvitation\UserStories\AnswersFeedbackQuestion;

use RC\Activities\User\RepliesToFeedbackInvitation\UserStories\AnswersFeedbackQuestion\Domain\Participant\AnsweredFeedbackQuestion;
use RC\Activities\User\RepliesToFeedbackInvitation\UserStories\AnswersFeedbackQuestion\Domain\Reply\NextReplyToUser;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\Bot\BotId\FromUuid;
use RC\Domain\FeedbackInvitation\ReadModel\FeedbackInvitation;
use RC\Domain\FeedbackInvitation\ReadModel\LatestByFeedbackDate;
use RC\Domain\FeedbackQuestion\FeedbackQuestion;
use RC\Domain\FeedbackQuestion\FirstNonAnsweredFeedbackQuestion;
use RC\Domain\Participant\ParticipantId\Impure\FromFeedbackInvitation as ParticipantIdFromFeedbackInvitation;
use RC\Domain\Participant\WriteModel\Participant;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use RC\Infrastructure\Logging\LogItem\InformationMessage;
use RC\Infrastructure\Logging\Logs;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Domain\Bot\BotToken\Impure\ByBotId;
use RC\Domain\TelegramBot\Reply\Text\Sorry;
use RC\Infrastructure\TelegramBot\UserId\Pure\FromParsedTelegramMessage;
use RC\Infrastructure\TelegramBot\UserMessage\Pure\FromParsedTelegramMessage as UserReply;
use RC\Infrastructure\TelegramBot\UserMessage\Pure\UserMessage;
use RC\Infrastructure\UserStory\Body\Emptie;
use RC\Infrastructure\UserStory\Existent;
use RC\Infrastructure\UserStory\Response;
use RC\Infrastructure\UserStory\Response\Successful;
use RC\Infrastructure\Uuid\FromString as UuidFromString;

class AnswersFeedbackQuestion extends Existent
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
        $this->logs->receive(new InformationMessage('User answers feedback question scenario started.'));

        $latestFeedbackInvitation = new LatestByFeedbackDate(new FromParsedTelegramMessage($this->message), $this->botId(), $this->connection);
        $currentlyAnsweredQuestion = $this->currentlyAnsweredQuestion($latestFeedbackInvitation);

        $participantValue = $this->participantAnsweredFeedbackQuestion($latestFeedbackInvitation, new UserReply($this->message), $currentlyAnsweredQuestion)->value();
        if (!$participantValue->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($participantValue));
            $this->sorry()->value();
            return new Successful(new Emptie());
        }

        $nextReply = $this->nextReplyToUser($latestFeedbackInvitation)->value();
        if (!$nextReply->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($nextReply));
            $this->sorry()->value();
            return new Successful(new Emptie());
        }

        $this->logs->receive(new InformationMessage('User answers feedback question scenario finished.'));

        return new Successful(new Emptie());
    }

    private function currentlyAnsweredQuestion(FeedbackInvitation $latestFeedbackInvitation): FeedbackQuestion
    {
        return
            new FirstNonAnsweredFeedbackQuestion(
                new ParticipantIdFromFeedbackInvitation($latestFeedbackInvitation),
                $this->connection
            );
    }

    private function participantAnsweredFeedbackQuestion(FeedbackInvitation $feedbackInvitation, UserMessage $userReply, FeedbackQuestion $feedbackQuestion): Participant
    {
        return new AnsweredFeedbackQuestion($feedbackInvitation, $userReply, $feedbackQuestion, $this->connection);
    }

    private function botId(): BotId
    {
        return new FromUuid(new UuidFromString($this->botId));
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

    private function nextReplyToUser(FeedbackInvitation $feedbackInvitation)
    {
        return
            new NextReplyToUser(
                $feedbackInvitation,
                new FromParsedTelegramMessage($this->message),
                new FromUuid(new UuidFromString($this->botId)),
                $this->httpTransport,
                $this->connection
            );
    }
}