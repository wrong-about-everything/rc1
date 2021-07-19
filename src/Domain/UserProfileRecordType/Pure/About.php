<?php

declare(strict_types=1);

namespace RC\Domain\UserProfileRecordType\Pure;

class About extends UserProfileRecordType
{
    public function value(): int
    {
        return 2;
    }
}