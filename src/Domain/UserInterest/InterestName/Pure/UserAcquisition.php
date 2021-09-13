<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestName\Pure;

class UserAcquisition extends InterestName
{
    public function value(): string
    {
        return 'Привлечение пользователей';
    }

    public function exists(): bool
    {
        return true;
    }
}