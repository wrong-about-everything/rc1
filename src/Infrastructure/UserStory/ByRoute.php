<?php

declare(strict_types=1);

namespace RC\Infrastructure\UserStory;

class ByRoute implements UserStory
{
    public function __construct(HttpRequest $request)
    {
    }

    public function response(): Response
    {
        // TODO: Implement value() method.
    }
}