<?php

declare(strict_types=1);

namespace RC\Domain\Participant\WriteModel;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface Participant
{
    /**
     * @return ImpureValue Participant id
     */
    public function value(): ImpureValue;
}