<?php

declare(strict_types=1);

namespace RC\Infrastructure\Filesystem\FileContents;

use RC\Infrastructure\Filesystem\FileContents;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class FromStdErr implements FileContents
{
    private $value;

    public function __construct()
    {
        $this->value = new Successful(new Present(file_get_contents('php://stderr')));
    }

    public function value(): ImpureValue
    {
        return $this->value;
    }
}