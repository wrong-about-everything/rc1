<?php

declare(strict_types=1);

namespace RC\Infrastructure\ExecutionEnvironmentAdapter;

use RC\Infrastructure\UserStory\UserStory;

interface RawPhpFpmWebService
{
    public function response(): void;
}