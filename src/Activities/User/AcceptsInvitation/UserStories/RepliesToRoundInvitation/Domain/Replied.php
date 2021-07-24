<?php

declare(strict_types=1);

namespace RC\Activities\User\AcceptsInvitation\UserStories\RepliesToRoundInvitation\Domain;

use RC\Domain\BooleanAnswer\BooleanAnswerName\FromUserMessage;
use RC\Domain\BooleanAnswer\BooleanAnswerName\No;
use RC\Domain\RoundInvitation\InvitationId\Impure\FromInvitation;
use RC\Domain\RoundInvitation\ReadModel\Invitation as ReadModelInvitation;
use RC\Domain\RoundInvitation\WriteModel\Accepted;
use RC\Domain\RoundInvitation\WriteModel\Declined;
use RC\Domain\RoundInvitation\WriteModel\Invitation;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\TelegramBot\UserMessage\Impure\NonSuccessful;
use RC\Infrastructure\TelegramBot\UserMessage\Pure\FromParsedTelegramMessage;

class Replied implements Invitation
{
    private $message;
    private $invitation;
    private $connection;
    private $cached;

    public function __construct(array $message, ReadModelInvitation $invitation, OpenConnection $connection)
    {
        $this->message = $message;
        $this->invitation = $invitation;
        $this->connection = $connection;
        $this->cached = null;
    }

    public function value(): ImpureValue
    {
        return $this->cached()->value();
    }

    private function cached()
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doCached();
        }

        return $this->cached;
    }

    private function doCached()
    {
        $invitationId = new FromInvitation($this->invitation);

        if ((new FromUserMessage(new FromParsedTelegramMessage($this->message)))->equals(new No())) {
            $declinedInvitationValue = (new Declined($invitationId, $this->connection))->value();
            if (!$declinedInvitationValue->isSuccessful()) {
                return new NonSuccessful($declinedInvitationValue);
            }

            return $invitationId;
        }

        $acceptedInvitationValue = (new Accepted($invitationId, $this->connection))->value();
        if (!$acceptedInvitationValue->isSuccessful()) {
            return new NonSuccessful($acceptedInvitationValue);
        }

        return $invitationId;
    }
}