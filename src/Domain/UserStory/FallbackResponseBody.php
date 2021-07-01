<?php

declare(strict_types=1);

namespace RC\Domain\UserStory;

use RC\Infrastructure\UserStory\Body;

class FallbackResponseBody extends Body
{
    public function value(): string
    {
        return 'Произошла ужасная ошибка. Если вы её видите, значит мы уже чиним.';
    }
}