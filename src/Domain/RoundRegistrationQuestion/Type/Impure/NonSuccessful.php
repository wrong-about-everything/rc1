<?php

declare(strict_types=1);

namespace RC\Domain\RoundRegistrationQuestion\Type\Impure;

use Exception;
use RC\Infrastructure\ImpureInteractions\ImpureValue;

class NonSuccessful extends RoundRegistrationQuestionType
{
    private $impureValue;

    public function __construct(ImpureValue $impureValue)
    {
        if ($impureValue->isSuccessful()) {
            throw new Exception('This class is only for non-successful values');
        }
        $this->impureValue = $impureValue;
    }

    public function value(): ImpureValue
    {
        return $this->impureValue;
    }

    public function exists(): ImpureValue
    {
        return $this->impureValue;
    }
}