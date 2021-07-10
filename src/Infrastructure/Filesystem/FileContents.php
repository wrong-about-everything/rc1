<?php

declare(strict_types=1);

namespace RC\Infrastructure\Filesystem;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface FileContents
{
    public function value(): ImpureValue;
}