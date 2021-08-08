<?php

declare(strict_types=1);

namespace RC\Activities\User\RepliesToFeedbackInvitation\UserStories\AcceptsOrDeclinesInvitation\Domain\FeedbackInvitation;

use RC\Domain\BooleanAnswer\BooleanAnswerName\FromUserMessage;
use RC\Domain\BooleanAnswer\BooleanAnswerName\No;
use RC\Domain\BooleanAnswer\BooleanAnswerName\Yes;
use RC\Domain\FeedbackInvitation\FeedbackInvitationId\Impure\FeedbackInvitationId;
use RC\Domain\FeedbackInvitation\FeedbackInvitationId\Impure\FromFeedbackInvitation;
use RC\Domain\FeedbackInvitation\ReadModel\FeedbackInvitation;
use RC\Domain\FeedbackInvitation\WriteModel\FeedbackInvitation as WriteModelFeedbackInvitation;
use RC\Domain\FeedbackInvitation\ReadModel\NonSuccessful;
use RC\Domain\FeedbackInvitation\WriteModel\Accepted;
use RC\Domain\FeedbackInvitation\WriteModel\Declined;
use RC\Infrastructure\ImpureInteractions\Error\AlarmDeclineWithDefaultUserMessage;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Failed;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\TelegramBot\UserMessage\Pure\FromParsedTelegramMessage;

class AcceptedOrDeclinedFeedbackInvitation implements WriteModelFeedbackInvitation
{
    private $message;
    private $feedbackInvitation;
    private $connection;
    private $concrete;

    public function __construct(array $message, FeedbackInvitation $invitation, OpenConnection $connection)
    {
        $this->message = $message;
        $this->feedbackInvitation = $invitation;
        $this->connection = $connection;
        $this->concrete = null;
    }

    public function value(): ImpureValue
    {
        return $this->concrete()->value();
    }

    private function concrete(): FeedbackInvitationId
    {
        if (is_null($this->concrete)) {
            $this->concrete = $this->doConcrete();
        }

        return $this->concrete;
    }

    private function doConcrete(): FeedbackInvitationId
    {
        $invitationId = new FromFeedbackInvitation($this->feedbackInvitation);

        if ((new FromUserMessage(new FromParsedTelegramMessage($this->message)))->equals(new No())) {
            $declinedInvitationValue = (new Declined($invitationId, $this->connection))->value();
            if (!$declinedInvitationValue->isSuccessful()) {
                return new FromFeedbackInvitation(new NonSuccessful($declinedInvitationValue));
            }
            return $invitationId;
        } elseif ((new FromUserMessage(new FromParsedTelegramMessage($this->message)))->equals(new Yes())) {
            $acceptedInvitationValue = (new Accepted($invitationId, $this->connection))->value();
            if (!$acceptedInvitationValue->isSuccessful()) {
                return new FromFeedbackInvitation(new NonSuccessful($acceptedInvitationValue));
            }

            return $invitationId;
        }

        return
            new FromFeedbackInvitation(
                new NonSuccessful(
                    new Failed(
                        new AlarmDeclineWithDefaultUserMessage(
                            'Feedback invitation answer is neither yes nor no. Either this user story was run by mistake or user replied with custom message instead of pushing a button.',
                            []
                        )
                    )
                )
            );
    }
}