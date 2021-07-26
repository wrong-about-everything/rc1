<?php

declare(strict_types=1);

namespace RC\Activities\User\AcceptsInvitation\UserStories\AnswersRoundRegistrationQuestion\Domain\Invitation;

use RC\Domain\UserInterest\InterestId\Pure\Single\Networking;
use RC\Domain\Participant\ReadModel\ByInvitationId;
use RC\Domain\Participant\WriteModel\Registered;
use RC\Domain\RoundInvitation\InvitationId\Impure\InvitationId;
use RC\Domain\RoundInvitation\WriteModel\Invitation;
use RC\Domain\RoundInvitation\WriteModel\UserRegistered;
use RC\Domain\RoundRegistrationQuestion\NextRoundRegistrationQuestion;
use RC\Domain\UserInterest\InterestId\Impure\Multiple\FromParticipant;
use RC\Domain\UserInterest\InterestId\Impure\Single\FromPure;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;

class UserRegisteredIfNoMoreQuestionsLeftOrHisInterestInNetworking implements Invitation
{
    private $invitationId;
    private $connection;

    private $cached;

    public function __construct(InvitationId $invitationId, OpenConnection $connection)
    {
        $this->invitationId = $invitationId;
        $this->connection = $connection;

        $this->cached = null;
    }

    public function value(): ImpureValue
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doValue();
        }

        return $this->cached;
    }

    private function doValue(): ImpureValue
    {
        if (
            !(new NextRoundRegistrationQuestion($this->invitationId, $this->connection))->value()->pure()->isPresent()
                ||
            (new FromParticipant(new ByInvitationId($this->invitationId, $this->connection)))->contain(new FromPure(new Networking()))
        ) {
            $invitationValue = (new UserRegistered($this->invitationId, $this->connection))->value();
            if (!$invitationValue->isSuccessful()) {
                return $invitationValue;
            }
            $registeredParticipant = (new Registered($this->invitationId, $this->connection))->value();
            if (!$registeredParticipant->isSuccessful()) {
                return $registeredParticipant;
            }
        }

        return $this->invitationId->value();
    }
}