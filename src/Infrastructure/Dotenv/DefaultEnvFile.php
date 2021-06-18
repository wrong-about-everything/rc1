<?php

declare(strict_types=1);

namespace RC\Infrastructure\Dotenv;

use Dotenv\Dotenv as OneAndOnly;

class DefaultEnvFile implements DotEnv
{
    private $concrete;

    public function __construct(OneAndOnly $concrete)
    {
        $this->concrete = $concrete;
    }

    public function load(): void
    {
        $this->concrete->load();
    }
}
