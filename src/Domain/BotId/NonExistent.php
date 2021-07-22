<?php

declare(strict_types=1);

namespace RC\Domain\BotId;

use Exception;

class NonExistent extends BotId
{
    public function value(): string
    {
        throw new Exception('Bot id does not exist');
    }

    public function exists(): bool
    {
        return false;
    }
}