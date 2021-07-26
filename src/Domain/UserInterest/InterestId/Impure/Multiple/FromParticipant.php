<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestId\Impure\Multiple;

use RC\Domain\Participant\ReadModel\Participant;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class FromParticipant extends UserInterestIds
{
    private $participant;

    public function __construct(Participant $participant)
    {
        $this->participant = $participant;
    }

    public function value(): ImpureValue
    {
        if (!$this->participant->value()->isSuccessful()) {
            return $this->participant->value();
        }

        return
            new Successful(
                new Present(
                    json_decode($this->participant->value()->pure()->raw()['interested_in'])
                )
            );
    }
}