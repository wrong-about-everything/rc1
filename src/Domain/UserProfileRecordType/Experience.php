<?php

declare(strict_types=1);

namespace RC\Domain\UserProfileRecordType;

class Experience extends UserProfileRecordType
{
    public function value(): int
    {
        return 1;
    }
}