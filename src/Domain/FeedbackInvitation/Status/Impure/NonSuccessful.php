<?php

declare(strict_types=1);

namespace RC\Domain\FeedbackInvitation\Status\Impure;

use Exception;
use RC\Infrastructure\ImpureInteractions\ImpureValue;

class NonSuccessful extends Status
{
    private $nonSuccessfulImpureValue;

    public function __construct(ImpureValue $nonSuccessfulImpureValue)
    {
        if ($nonSuccessfulImpureValue->isSuccessful()) {
            throw new Exception('This class is only for non-successful values');
        }

        $this->nonSuccessfulImpureValue = $nonSuccessfulImpureValue;
    }

    public function value(): ImpureValue
    {
        return $this->nonSuccessfulImpureValue;
    }

    function exists(): ImpureValue
    {
        return $this->nonSuccessfulImpureValue;
    }
}