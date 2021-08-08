<?php

declare(strict_types=1);

namespace RC\Domain\FeedbackQuestion;

use RC\Domain\Participant\ParticipantId\Impure\ParticipantId;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;

class FirstNonAnsweredFeedbackQuestion implements FeedbackQuestion
{
    private $participantId;
    private $connection;
    private $cached;

    public function __construct(ParticipantId $participantId, OpenConnection $connection)
    {
        $this->participantId = $participantId;
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

    private function doValue()
    {
        if (!$this->participantId->value()->isSuccessful()) {
            return $this->participantId->value();
        }

        $response =
            (new Selecting(
                <<<q
select fq.*
from feedback_question fq
    join meeting_round_participant mrp on mrp.meeting_round_id = fq.meeting_round_id
    left join user_feedback_progress ufp on ufp.participant_id = mrp.id
where mrp.id = ? and ufp.feedback_question_id is null
order by fq.ordinal_number asc
limit 1
q
                ,
                [$this->participantId->value()->pure()->raw()],
                $this->connection
            ))
                ->response();
        if (!$response->isSuccessful()) {
            return $response;
        }
        if (!isset($response->pure()->raw()[0])) {
            return new Successful(new Emptie());
        }

        return new Successful(new Present($response->pure()->raw()[0]));
    }
}