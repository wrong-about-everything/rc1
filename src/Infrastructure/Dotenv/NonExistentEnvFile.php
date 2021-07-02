<?php

declare(strict_types=1);

namespace RC\Infrastructure\Dotenv;

class NonExistentEnvFile implements DotEnv
{
    public function load(): void
    {
    }
}
