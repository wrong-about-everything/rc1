<?php

declare(strict_types=1);

namespace RC\Domain\BooleanAnswer\BooleanAnswerName;

class FromString extends BooleanAnswerName
{
    private $booleanAnswerName;

    public function __construct(string $booleanAnswer)
    {
        $this->booleanAnswerName = $this->concrete($booleanAnswer);
    }

    public function value(): string
    {
        return $this->booleanAnswerName->value();
    }

    public function exists(): bool
    {
        return $this->booleanAnswerName->exists();
    }

    private function concrete(string $booleanAnswer): BooleanAnswerName
    {
        return [
            (new No())->value() => new No(),
            (new Yes())->value() => new Yes(),
        ][$booleanAnswer] ?? new NonExistent();
    }
}