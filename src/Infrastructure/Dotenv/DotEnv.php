<?php

declare(strict_types=1);

namespace RC\Infrastructure\Dotenv;

interface DotEnv
{
    public function load(): void;
}
