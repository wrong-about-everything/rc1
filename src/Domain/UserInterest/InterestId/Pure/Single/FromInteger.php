<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestId\Pure\Single;

class FromInteger extends InterestId
{
    private $concrete;

    public function __construct(int $value)
    {
        $this->concrete = $this->all()[$value] ?? new NonExistent();
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
            (new Networking())->value() => new Networking(),
            (new SpecificArea())->value() => new SpecificArea(),
            (new SkySurfing())->value() => new SkySurfing(),
            (new DayDreaming())->value() => new DayDreaming(),
        ];
    }
}