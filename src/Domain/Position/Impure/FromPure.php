<?php

declare(strict_types=1);

namespace RC\Domain\Position\Impure;

use RC\Domain\Position\Pure\Position as PurePosition;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class FromPure extends Position
{
    private $position;

    public function __construct(PurePosition $position)
    {
        $this->position = $position;
    }

    public function value(): ImpureValue
    {
        return new Successful(new Present($this->position->value()));
    }

    public function exists(): ImpureValue
    {
        return new Successful(new Present($this->position->exists()));
    }
}