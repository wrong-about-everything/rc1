<?php

declare(strict_types=1);

namespace RC\Infrastructure\Filesystem\FileContents;

use RC\Infrastructure\Filesystem\FileContents;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class FromString implements FileContents
{
    private $content;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function value(): ImpureValue
    {
        return new Successful(new Present($this->content));
    }
}