<?php

declare(strict_types=1);

namespace RC\Activities\User\RepliesToRoundInvitation\UserStories\AcceptsOrDeclinesInvitation;

use RC\Activities\User\RepliesToRoundInvitation\UserStories\AcceptsOrDeclinesInvitation\Domain\Participant\RepliedToInvitation;
use RC\Activities\User\RepliesToRoundInvitation\UserStories\AcceptsOrDeclinesInvitation\Domain\Reply\NextReplyToUser;
use RC\Domain\Bot\BotId\FromUuid;
use RC\Domain\Participant\WriteModel\Participant;
use RC\Domain\RoundInvitation\InvitationId\Impure\FromInvitation as InvitationIdFromInvitation;
use RC\Domain\RoundInvitation\InvitationId\Impure\InvitationId;
use RC\Domain\RoundInvitation\ReadModel\Invitation;
use RC\Domain\RoundInvitation\ReadModel\InvitationForMeetingRoundWithLatestPassedInvitationDate;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use RC\Infrastructure\Logging\LogItem\InformationMessage;
use RC\Infrastructure\Logging\Logs;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Domain\Bot\BotToken\Impure\ByBotId;
use RC\Domain\SentReplyToUser\Sorry;
use RC\Infrastructure\TelegramBot\UserId\Pure\FromParsedTelegramMessage;
use RC\Infrastructure\TelegramBot\UserId\Pure\FromParsedTelegramMessage as UserIdFromParsedTelegramMessage;
use RC\Infrastructure\UserStory\Body\Emptie;
use RC\Infrastructure\UserStory\Existent;
use RC\Infrastructure\UserStory\Response;
use RC\Infrastructure\UserStory\Response\Successful;
use RC\Infrastructure\Uuid\FromString as UuidFromString;

class AcceptsOrDeclinesInvitation extends Existent
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
        $this->logs->receive(new InformationMessage('User replies to round invitation scenario started.'));

        $invitation = $this->invitation();
        if (!$invitation->value()->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($invitation->value()));
            $this->sorry()->value();
            return new Successful(new Emptie());
        }

        $participantRepliedToInvitationValue = $this->participantRepliedToInvitation($invitation)->value();
        if (!$participantRepliedToInvitationValue->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($participantRepliedToInvitationValue));
            $this->sorry()->value();
            return new Successful(new Emptie());
        }

        $nextReply = $this->nextReplyToUser(new InvitationIdFromInvitation($invitation))->value();
        if (!$nextReply->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($nextReply));
            $this->sorry()->value();
            return new Successful(new Emptie());
        }

        $this->logs->receive(new InformationMessage('User replies to round invitation scenario finished.'));

        return new Successful(new Emptie());
    }

    private function invitation(): Invitation
    {
        return
            new InvitationForMeetingRoundWithLatestPassedInvitationDate(
                new UserIdFromParsedTelegramMessage($this->message),
                new FromUuid(new UuidFromString($this->botId)),
                $this->connection
            );
    }

    private function participantRepliedToInvitation(Invitation $invitation): Participant
    {
        return
            new RepliedToInvitation(
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

    private function nextReplyToUser(InvitationId $invitationId)
    {
        return
            new NextReplyToUser(
                $invitationId,
                new FromParsedTelegramMessage($this->message),
                new FromUuid(new UuidFromString($this->botId)),
                $this->httpTransport,
                $this->connection
            );
    }
}