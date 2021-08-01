<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestName\Pure;

class FromString extends InterestName
{
    private $concrete;

    public function __construct(string $interestName)
    {
        $this->concrete = $this->all()[$interestName] ?? new NonExistent();
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
            (new Networking())->value() => new Networking(),
            (new SpecificArea())->value() => new SpecificArea(),
            (new SkySurfing())->value() => new SkySurfing(),
            (new DayDreaming())->value() => new DayDreaming(),
        ];
    }
}