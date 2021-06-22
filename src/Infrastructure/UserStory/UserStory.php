<?php

declare(strict_types=1);

namespace RC\Infrastructure\UserStory;

interface UserStory
{
    public function response(): Response;
}