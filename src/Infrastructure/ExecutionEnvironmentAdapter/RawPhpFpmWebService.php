<?php

declare(strict_types=1);

namespace RC\Infrastructure\ExecutionEnvironmentAdapter;

interface RawPhpFpmWebService
{
    public function response(): void;
}