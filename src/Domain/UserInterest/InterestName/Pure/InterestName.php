<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestName\Pure;

abstract class InterestName
{
    abstract public function value(): string;

    abstract public function exists(): bool;
}