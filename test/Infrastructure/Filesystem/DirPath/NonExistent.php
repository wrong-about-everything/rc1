<?php

declare(strict_types=1);

namespace RC\Tests\Infrastructure\Filesystem\DirPath;

use RC\Infrastructure\Filesystem\DirPath;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class NonExistent extends DirPath
{
    public function value(): ImpureValue
    {
        return new Successful(new Present('/most/likely/i/do/not/exist'));
    }

    public function exists(): bool
    {
        return file_exists('/most/likely/i/do/not/exist');
    }
}