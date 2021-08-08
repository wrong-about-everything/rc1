<?php

declare(strict_types=1);

namespace RC\Domain\FeedbackInvitation\ReadModel;

use RC\Domain\FeedbackInvitation\FeedbackInvitationId\Impure\FromFeedbackInvitation;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;

class Refreshed implements FeedbackInvitation
{
    private $feedbackInvitation;
    private $connection;
    private $cached;

    public function __construct(FeedbackInvitation $feedbackInvitation, OpenConnection $connection)
    {
        $this->feedbackInvitation = $feedbackInvitation;
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
        return (new ById(new FromFeedbackInvitation($this->feedbackInvitation), $this->connection))->value();
    }
}