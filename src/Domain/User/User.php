<?php

declare(strict_types=1);

namespace RC\Domain\User;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface User
{
    public function value(): ImpureValue;
}