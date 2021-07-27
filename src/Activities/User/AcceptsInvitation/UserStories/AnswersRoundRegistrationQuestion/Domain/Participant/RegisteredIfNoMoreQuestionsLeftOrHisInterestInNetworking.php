<?php

declare(strict_types=1);

namespace RC\Activities\User\AcceptsInvitation\UserStories\AnswersRoundRegistrationQuestion\Domain\Participant;

use RC\Domain\Participant\ParticipantId\Impure\FromReadModelParticipant;
use RC\Domain\Participant\WriteModel\Participant;
use RC\Domain\UserInterest\InterestId\Pure\Single\Networking;
use RC\Domain\Participant\ReadModel\ByInvitationId;
use RC\Domain\Participant\WriteModel\Registered;
use RC\Domain\RoundInvitation\InvitationId\Impure\InvitationId;
use RC\Domain\RoundRegistrationQuestion\NextRoundRegistrationQuestion;
use RC\Domain\UserInterest\InterestId\Impure\Multiple\FromParticipant;
use RC\Domain\UserInterest\InterestId\Impure\Single\FromPure;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;

class RegisteredIfNoMoreQuestionsLeftOrHisInterestInNetworking implements Participant
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
        $participant = new ByInvitationId($this->invitationId, $this->connection);
        if (
            !(new NextRoundRegistrationQuestion($this->invitationId, $this->connection))->value()->pure()->isPresent()
                ||
            (new FromParticipant($participant))->contain(new FromPure(new Networking()))
        ) {
            $registeredParticipant = (new Registered($this->invitationId, $this->connection))->value();
            if (!$registeredParticipant->isSuccessful()) {
                return $registeredParticipant;
            }
        }

        return (new FromReadModelParticipant($participant))->value();
    }
}