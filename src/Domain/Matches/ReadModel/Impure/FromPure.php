<?php

declare(strict_types=1);

namespace RC\Domain\Matches\ReadModel\Impure;

use RC\Domain\Matches\ReadModel\Pure\Matches as PureMatches;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class FromPure implements Matches
{
    private $pureMatches;

    public function __construct(PureMatches $pureMatches)
    {
        $this->pureMatches = $pureMatches;
    }

    public function value(): ImpureValue
    {
        return new Successful(new Present($this->pureMatches->value()));
    }

}