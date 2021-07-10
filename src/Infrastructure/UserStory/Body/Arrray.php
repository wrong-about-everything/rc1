<?php

declare(strict_types=1);

namespace RC\Infrastructure\UserStory\Body;

use RC\Infrastructure\ImpureInteractions\PureValue;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\UserStory\Body;

class Arrray extends Body
{
    private $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function value(): PureValue
    {
        return new Present($this->payload);
    }
}