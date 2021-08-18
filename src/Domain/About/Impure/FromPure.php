<?php

declare(strict_types=1);

namespace RC\Domain\About\Impure;

use RC\Domain\About\Pure\About as PureAbout;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class FromPure implements About
{
    private $pureAbout;

    public function __construct(PureAbout $pureAbout)
    {
        $this->pureAbout = $pureAbout;
    }

    public function value(): ImpureValue
    {
        return new Successful(new Present($this->pureAbout->value()));
    }

    public function empty(): ImpureValue
    {
        return new Successful(new Present($this->pureAbout->empty()));
    }

    public function exists(): ImpureValue
    {
        return new Successful(new Present($this->pureAbout->exists()));
    }
}
