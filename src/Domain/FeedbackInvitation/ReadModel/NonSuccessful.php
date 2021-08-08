<?php

declare(strict_types=1);

namespace RC\Domain\FeedbackInvitation\ReadModel;

use Exception;
use RC\Infrastructure\ImpureInteractions\ImpureValue;

class NonSuccessful implements FeedbackInvitation
{
    private $nonSuccessfulImpureValue;

    public function __construct(ImpureValue $nonSuccessfulImpureValue)
    {
        if ($nonSuccessfulImpureValue->isSuccessful()) {
            throw new Exception('This class can be used onl with non-successful impure values');
        }

        $this->nonSuccessfulImpureValue = $nonSuccessfulImpureValue;
    }

    public function value(): ImpureValue
    {
        return $this->nonSuccessfulImpureValue;
    }
}