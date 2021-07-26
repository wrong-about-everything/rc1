<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestId\Pure\Single;

use RC\Domain\UserInterest\InterestName\Pure\InterestName;
use RC\Domain\UserInterest\InterestName\Pure\Networking as NetworkingName;
use RC\Domain\UserInterest\InterestName\Pure\SpecificArea as SpecificAreaName;

class FromInterestName extends InterestId
{
    private $concrete;

    public function __construct(InterestName $interestName)
    {
        $this->concrete = $this->all()[$interestName->value()] ?? new NonExistent();
    }

    public function value(): int
    {
        return $this->concrete->value();
    }

    public function exists(): bool
    {
        return $this->concrete->exists();
    }

    private function all()
    {
        return [
            (new NetworkingName())->value() => new Networking(),
            (new SpecificAreaName())->value() => new SpecificArea(),
        ];
    }
}