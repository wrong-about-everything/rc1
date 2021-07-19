<?php

declare(strict_types=1);

namespace RC\Domain\Experience\Pure;

class FromInteger extends Experience
{
    private $concrete;

    public function __construct(int $experience)
    {
        $this->concrete = isset($this->all()[$experience]) ? $this->all()[$experience] : new NonExistent();
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
            (new LessThatAYear())->value() => new LessThatAYear()
        ];
    }
}