<?php

declare(strict_types=1);

namespace RC\Activities\User\RepliesToRoundInvitation\UserStories\AcceptsOrDeclinesInvitation\Domain\Participant;

use RC\Domain\BooleanAnswer\BooleanAnswerId\Pure\FromBooleanAnswerName;
use RC\Domain\BooleanAnswer\BooleanAnswerId\Pure\No;
use RC\Domain\BooleanAnswer\BooleanAnswerId\Pure\Yes;
use RC\Domain\BooleanAnswer\BooleanAnswerName\FromUserMessage;
use RC\Domain\Participant\WriteModel\AcceptedInvitation;
use RC\Domain\Participant\WriteModel\NonExistent;
use RC\Domain\Participant\WriteModel\NonSuccessful;
use RC\Domain\Participant\WriteModel\Participant;
use RC\Domain\RoundInvitation\InvitationId\Impure\FromInvitation;
use RC\Domain\RoundInvitation\ReadModel\Invitation as ReadModelInvitation;
use RC\Domain\RoundInvitation\WriteModel\Accepted;
use RC\Domain\RoundInvitation\WriteModel\Declined;
use RC\Infrastructure\ImpureInteractions\Error\AlarmDeclineWithDefaultUserMessage;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Failed;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\TelegramBot\UserMessage\Pure\FromParsedTelegramMessage;

class RepliedToInvitation implements Participant
{
    private $message;
    private $invitation;
    private $connection;
    private $concrete;

    public function __construct(array $message, ReadModelInvitation $invitation, OpenConnection $connection)
    {
        $this->message = $message;
        $this->invitation = $invitation;
        $this->connection = $connection;
        $this->concrete = null;
    }

    public function value(): ImpureValue
    {
        return $this->concrete()->value();
    }

    private function concrete(): Participant
    {
        if (is_null($this->concrete)) {
            $this->concrete = $this->doConcrete();
        }

        return $this->concrete;
    }

    private function doConcrete(): Participant
    {
        $invitationId = new FromInvitation($this->invitation);

        if ($this->no()) {
            $declinedInvitationValue = (new Declined($invitationId, $this->connection))->value();
            if (!$declinedInvitationValue->isSuccessful()) {
                return new NonSuccessful($declinedInvitationValue);
            }

            return new NonExistent();
        } elseif ($this->yes()) {
            $acceptedInvitationValue = (new Accepted($invitationId, $this->connection))->value();
            if (!$acceptedInvitationValue->isSuccessful()) {
                return new NonSuccessful($acceptedInvitationValue);
            }

            $participant = new AcceptedInvitation($invitationId, $this->connection);
            if (!$participant->value()->isSuccessful()) {
                return new NonSuccessful($participant->value());
            }

            return $participant;
        }

        return
            new NonSuccessful(
                new Failed(
                    new AlarmDeclineWithDefaultUserMessage(
                        'An invitation reply answer is neither yes nor no. Either this user story was run by mistake or user replied with custom message instead of pushing a button.',
                        []
                    )
                )
            );
    }

    private function no()
    {
        return
            (new FromBooleanAnswerName(
                new FromUserMessage(
                    new FromParsedTelegramMessage($this->message)
                )
            ))
                ->equals(new No());
    }

    private function yes()
    {
        return
            (new FromBooleanAnswerName(
                (new FromUserMessage(
                    new FromParsedTelegramMessage($this->message)
                ))
            ))
                ->equals(new Yes());
    }
}