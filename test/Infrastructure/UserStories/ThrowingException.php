<?php

declare(strict_types=1);

namespace RC\Tests\Infrastructure\UserStories;

use Exception;
use RC\Infrastructure\UserStory\Existent;
use RC\Infrastructure\UserStory\Response;

class ThrowingException extends Existent
{
    public function response(): Response
    {
        throw new Exception('Oh shit!');
    }
}