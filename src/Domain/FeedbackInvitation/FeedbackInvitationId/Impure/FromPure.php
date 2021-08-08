<?php

declare(strict_types=1);

namespace RC\Domain\FeedbackInvitation\FeedbackInvitationId\Impure;

use RC\Domain\FeedbackInvitation\FeedbackInvitationId\Pure\FeedbackInvitationId as PureFeedbackInvitationId;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class FromPure implements FeedbackInvitationId
{
    private $pureFeedbackInvitationId;

    public function __construct(PureFeedbackInvitationId $pureFeedbackInvitationId)
    {
        $this->pureFeedbackInvitationId = $pureFeedbackInvitationId;
    }

    public function value(): ImpureValue
    {
        return new Successful(new Present($this->pureFeedbackInvitationId->value()));
    }

    public function exists(): ImpureValue
    {
        return new Successful(new Present($this->pureFeedbackInvitationId->exists()));
    }
}