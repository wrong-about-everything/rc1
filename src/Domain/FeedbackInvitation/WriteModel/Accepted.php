<?php

declare(strict_types=1);

namespace RC\Domain\FeedbackInvitation\WriteModel;

use RC\Domain\FeedbackInvitation\FeedbackInvitationId\Impure\FeedbackInvitationId;
use RC\Domain\FeedbackInvitation\Status\Pure\Accepted as AcceptedStatus;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;

class Accepted implements FeedbackInvitation
{
    private $feedbackInvitationId;
    private $connection;
    private $cached;

    public function __construct(FeedbackInvitationId $feedbackInvitationId, OpenConnection $connection)
    {
        $this->feedbackInvitationId = $feedbackInvitationId;
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
        if (!$this->feedbackInvitationId->value()->isSuccessful()) {
            return $this->feedbackInvitationId->value();
        }

        $updatedStatus =
            (new SingleMutating(
                <<<q
update feedback_invitation
set status = ?
where id = ?
q
                ,
                [(new AcceptedStatus())->value(), $this->feedbackInvitationId->value()->pure()->raw()],
                $this->connection
            ))
                ->response();
        if (!$updatedStatus->isSuccessful()) {
            return $updatedStatus;
        }

        return new Successful(new Present($this->feedbackInvitationId->value()));
    }
}