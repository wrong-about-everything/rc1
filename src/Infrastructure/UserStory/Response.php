<?php

declare(strict_types=1);

namespace RC\Infrastructure\UserStory;

interface Response
{
    public function isSuccessful(): bool;

    public function code(): Code;

    public function headers(): Headers;

    public function body(): string;
}