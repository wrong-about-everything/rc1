<?php

declare(strict_types=1);

namespace RC\Domain\Participant\ParticipantId\Impure;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface ParticipantId
{
    public function value(): ImpureValue;

    public function exists(): ImpureValue;
}