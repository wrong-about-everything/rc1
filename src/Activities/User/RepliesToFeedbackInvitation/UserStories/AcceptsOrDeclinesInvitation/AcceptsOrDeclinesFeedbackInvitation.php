<?php

declare(strict_types=1);

namespace RC\Activities\User\RepliesToFeedbackInvitation\UserStories\AcceptsOrDeclinesInvitation;

use RC\Activities\User\RepliesToFeedbackInvitation\UserStories\AcceptsOrDeclinesInvitation\Domain\FeedbackInvitation\AcceptedOrDeclinedFeedbackInvitation;
use RC\Activities\User\RepliesToFeedbackInvitation\UserStories\AcceptsOrDeclinesInvitation\Domain\Reply\NextReplyToUser;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\Bot\BotId\FromUuid;
use RC\Domain\FeedbackInvitation\ReadModel\FeedbackInvitation;
use RC\Domain\FeedbackInvitation\ReadModel\LatestByFeedbackDate;
use RC\Domain\FeedbackInvitation\ReadModel\Refreshed;
use RC\Domain\FeedbackInvitation\WriteModel\FeedbackInvitation as WriteModelFeedbackInvitation;
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
use RC\Infrastructure\Uuid\FromString;
use RC\Infrastructure\Uuid\FromString as UuidFromString;

class AcceptsOrDeclinesFeedbackInvitation extends Existent
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
        $this->logs->receive(new InformationMessage('User replies to feedback invitation scenario started.'));

        $feedbackInvitation = $this->feedbackInvitation();
        if (!$feedbackInvitation->value()->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($feedbackInvitation->value()));
            $this->sorry()->value();
            return new Successful(new Emptie());
        }

        $acceptedOrDeclinedFeedbackInvitation = $this->acceptedOrDeclinedFeedbackInvitation($feedbackInvitation)->value();
        if (!$acceptedOrDeclinedFeedbackInvitation->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($acceptedOrDeclinedFeedbackInvitation));
            $this->sorry()->value();
            return new Successful(new Emptie());
        }

        $nextReply = $this->nextReplyToUser(new Refreshed($feedbackInvitation, $this->connection))->value();
        if (!$nextReply->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($nextReply));
            $this->sorry()->value();
            return new Successful(new Emptie());
        }

        $this->logs->receive(new InformationMessage('User replies to feedback invitation scenario finished.'));

        return new Successful(new Emptie());
    }

    private function feedbackInvitation(): FeedbackInvitation
    {
        return
            new LatestByFeedbackDate(
                new FromParsedTelegramMessage($this->message),
                $this->botId(),
                $this->connection
            );
    }

    private function botId(): BotId
    {
        return new FromUuid(new FromString($this->botId));
    }

    private function acceptedOrDeclinedFeedbackInvitation(FeedbackInvitation $invitation): WriteModelFeedbackInvitation
    {
        return
            new AcceptedOrDeclinedFeedbackInvitation(
                $this->message,
                $invitation,
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