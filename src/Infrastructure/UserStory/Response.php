<?php

declare(strict_types=1);

namespace RC\Infrastructure\UserStory;

interface Response
{
    public function isSuccessful(): bool;

    public function value(): Response;

    public function error(): Error;
}