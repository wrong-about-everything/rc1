<?php

declare(strict_types=1);

namespace RC\Domain\RoundRegistrationQuestion;

use RC\Domain\RoundInvitation\InvitationId\Impure\InvitationId;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;

class NextRoundRegistrationQuestion implements RoundRegistrationQuestion
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
        // @todo: ЗДесь будет ошибка!
        $roundRegistrationQuestion =
            (new Selecting(
                <<<q
        select mrrq.*
        from meeting_round_invitation mri
            join meeting_round_registration_question mrrq on mri.meeting_round_id = mrrq.meeting_round_id
            left join user_round_registration_progress urrp on urrp.user_id = mri.user_id and mrrq.id = urrp.registration_question_id
        where mri.id = ? and urrp.registration_question_id is null
        order by mrrq.ordinal_number asc
        limit 1
        q
                ,
                [$this->invitationId->value()->pure()->raw()],
                $this->connection
            ))
                ->response();
        if (!$roundRegistrationQuestion->isSuccessful()) {
            return $roundRegistrationQuestion;
        }
        if (!isset($roundRegistrationQuestion->pure()->raw()[0])) {
            return new Successful(new Emptie());
        }

        return new Successful(new Present($roundRegistrationQuestion->pure()->raw()[0]));
    }
}