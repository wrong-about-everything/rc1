<?php

declare(strict_types=1);

namespace RC\Domain\BooleanAnswer\BooleanAnswerName;

class No extends BooleanAnswerName
{
    public function value(): string
    {
        return 'Нет';
    }

    public function exists(): bool
    {
        return true;
    }
}