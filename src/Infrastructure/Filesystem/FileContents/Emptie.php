<?php

declare(strict_types=1);

namespace RC\Infrastructure\Filesystem\FileContents;

use RC\Infrastructure\Filesystem\FileContents;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class Emptie implements FileContents
{
    public function value(): ImpureValue
    {
        return new Successful(new Present(''));
    }
}