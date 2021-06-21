<?php

declare(strict_types=1);

namespace RC\Infrastructure\UserStory;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface UserStory
{
    public function value(): Response;
}