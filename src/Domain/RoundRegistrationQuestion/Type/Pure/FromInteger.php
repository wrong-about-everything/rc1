<?php

declare(strict_types=1);

namespace RC\Domain\RoundRegistrationQuestion\Type\Pure;

class FromInteger extends RoundRegistrationQuestionType
{
    private $concrete;

    public function __construct(int $type)
    {
        $this->concrete = $this->all()[$type] ?? new NonExistent();
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
            (new NetworkingOrSomeSpecificArea())->value() => new NetworkingOrSomeSpecificArea(),
            (new SpecificAreaChoosing())->value() => new SpecificAreaChoosing(),
        ];
    }
}