<?php

declare(strict_types=1);

namespace RC\Domain\AnswerOptions;

use RC\Domain\RoundInvitation\InvitationId\Impure\InvitationId;
use RC\Domain\RoundRegistrationQuestion\RoundRegistrationQuestion;
use RC\Domain\RoundRegistrationQuestion\Type\Impure\FromPure;
use RC\Domain\RoundRegistrationQuestion\Type\Impure\FromRoundRegistrationQuestion as RoundRegistrationQuestionType;
use RC\Domain\RoundRegistrationQuestion\Type\Pure\NetworkingOrSomeSpecificArea;
use RC\Domain\UserInterest\InterestId\Impure\Multiple\AvailableInterestIdsInRoundByInvitationId;
use RC\Domain\UserInterest\InterestId\Pure\Single\FromInteger;
use RC\Domain\UserInterest\InterestName\Pure\FromInterestId;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;

class FromRoundRegistrationQuestion implements AnswerOptions
{
    private $registrationQuestion;
    private $invitationId;
    private $connection;
    private $cached;

    public function __construct(RoundRegistrationQuestion $roundRegistrationQuestion, InvitationId $invitationId, OpenConnection $connection)
    {
        $this->registrationQuestion = $roundRegistrationQuestion;
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
        if (!$this->registrationQuestion->value()->isSuccessful()) {
            return $this->registrationQuestion->value();
        }

        if ((new RoundRegistrationQuestionType($this->registrationQuestion))->equals(new FromPure(new NetworkingOrSomeSpecificArea()))) {
            return
                new Successful(
                    new Present(
                        array_map(
                            function (int $interest) {
                                return [['text' => (new FromInterestId(new FromInteger($interest)))->value()]];
                            },
                            (new AvailableInterestIdsInRoundByInvitationId($this->invitationId, $this->connection))->value()->pure()->raw()
                        )
                    )
                );
        }

        return new Successful(new Present([]));
    }
}