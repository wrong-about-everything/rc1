<?php

declare(strict_types=1);

namespace RC\Domain\BooleanAnswer\BooleanAnswerId\Pure;

class FromInteger extends BooleanAnswer
{
    private $concrete;

    public function __construct(int $booleanAnswer)
    {
        $this->concrete = isset($this->all()[$booleanAnswer]) ? $this->all()[$booleanAnswer] : new NonExistent();
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
            (new No())->value() => new No(),
            (new Yes())->value() => new Yes(),
        ];
    }
}