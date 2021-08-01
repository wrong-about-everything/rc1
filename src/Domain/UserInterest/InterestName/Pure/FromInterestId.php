<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestName\Pure;

use RC\Domain\UserInterest\InterestId\Pure\Single\DayDreaming as DayDreamingId;
use RC\Domain\UserInterest\InterestId\Pure\Single\InterestId;
use RC\Domain\UserInterest\InterestId\Pure\Single\Networking as NetworkingId;
use RC\Domain\UserInterest\InterestId\Pure\Single\SkySurfing as SkySurfingId;
use RC\Domain\UserInterest\InterestId\Pure\Single\SpecificArea as SpecificAreaId;

class FromInterestId extends InterestName
{
    private $concrete;

    public function __construct(InterestId $interestId)
    {
        $this->concrete = $this->all()[$interestId->value()] ?? new NonExistent();
    }

    public function value(): string
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
            (new NetworkingId())->value() => new Networking(),
            (new SpecificAreaId())->value() => new SpecificArea(),
            (new SkySurfingId())->value() => new SkySurfing(),
            (new DayDreamingId())->value() => new DayDreaming(),
        ];
    }
}