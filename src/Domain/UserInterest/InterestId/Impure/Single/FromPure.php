<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestId\Impure\Single;

use RC\Domain\UserInterest\InterestId\Pure\Single\InterestId as PureUserInterest;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class FromPure extends InterestId
{
    private $userInterest;

    public function __construct(PureUserInterest $userInterest)
    {
        $this->userInterest = $userInterest;
    }

    public function value(): ImpureValue
    {
        return new Successful(new Present($this->userInterest->value()));
    }
}