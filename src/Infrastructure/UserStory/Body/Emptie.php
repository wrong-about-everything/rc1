<?php

declare(strict_types=1);

namespace RC\Infrastructure\UserStory\Body;

use RC\Infrastructure\ImpureInteractions\PureValue;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie as EmptieValue;
use RC\Infrastructure\UserStory\Body;

class Emptie extends Body
{
    public function value(): PureValue
    {
        return new EmptieValue();
    }
}