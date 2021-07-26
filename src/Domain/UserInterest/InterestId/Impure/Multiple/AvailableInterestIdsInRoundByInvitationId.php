<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestId\Impure\Multiple;

use RC\Domain\RoundInvitation\InvitationId\Impure\InvitationId;
use RC\Domain\UserInterest\InterestId\Impure\Multiple\UserInterestIds;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;

class AvailableInterestIdsInRoundByInvitationId extends UserInterestIds
{
    private $invitationId;
    private $connection;

    public function __construct(InvitationId $invitationId, OpenConnection $connection)
    {
        $this->invitationId = $invitationId;
        $this->connection = $connection;
    }

    public function value(): ImpureValue
    {
        if (!$this->invitationId->value()->isSuccessful()) {
            return $this->invitationId->value();
        }

        $aimsFromDatabase =
            (new Selecting(
                <<<q
select mr.available_aims
from meeting_round_invitation mri
    join meeting_round mr on mri.meeting_round_id = mr.id
where mri.id = ?
q
                ,
                [$this->invitationId->value()->pure()->raw()],
                $this->connection
            ))
                ->response();
        if (!$aimsFromDatabase->isSuccessful()) {
            return $aimsFromDatabase;
        }

        return
            new Successful(
                new Present(
                    json_decode(
                        $aimsFromDatabase->pure()->raw()[0]['available_aims'] ?? json_encode([]),
                        true
                    )
                )
            );
    }
}