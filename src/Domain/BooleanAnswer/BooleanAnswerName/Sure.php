<?php

declare(strict_types=1);

namespace RC\Domain\BooleanAnswer\BooleanAnswerName;

class Sure extends BooleanAnswerName
{
    public function value(): string
    {
        return 'Конечно!';
    }

    public function exists(): bool
    {
        return true;
    }
}