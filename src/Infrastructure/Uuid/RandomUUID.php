<?php

declare(strict_types=1);

namespace RC\Infrastructure\Uuid;

use Exception;
use Ramsey\Uuid\Uuid as RamseyUuid;

class RandomUUID implements UUID
{
    /**
     * @throws Exception
     */
    public function value(): string
    {
        return RamseyUuid::uuid4()->toString();
    }
}
