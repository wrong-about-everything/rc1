<?php

declare(strict_types=1);

namespace RC\Domain\FeedbackInvitation\ReadModel;

use RC\Domain\FeedbackInvitation\FeedbackInvitationId\Impure\FeedbackInvitationId;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;

class ById implements FeedbackInvitation
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

    private function doValue()
    {
        if (!$this->feedbackInvitationId->value()->isSuccessful()) {
            return $this->feedbackInvitationId->value();
        }

        $dbResponse =
            (new Selecting(
                'select fi.* from feedback_invitation fi where fi.id = ?'                ,
                [$this->feedbackInvitationId->value()->pure()->raw()],
                $this->connection
            ))
                ->response();
        if (!$dbResponse->isSuccessful()) {
            return $dbResponse;
        }
        if (!isset($dbResponse->pure()->raw()[0])) {
            return new Successful(new Emptie());
        }

        return new Successful(new Present($dbResponse->pure()->raw()[0]));
    }
}