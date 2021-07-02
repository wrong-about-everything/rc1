<?php

declare(strict_types=1);

namespace RC\Infrastructure\SqlDatabase\Agnostic;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface Query
{
    public function response(): ImpureValue;
}
