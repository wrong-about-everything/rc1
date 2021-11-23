<?php

declare(strict_types=1);

namespace RC\Domain\UserStory\Body;

use RC\Infrastructure\ImpureInteractions\PureValue;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\UserStory\Body;

class TelegramFallbackResponseBody extends Body
{
    public function value(): PureValue
    {
        return new Present('Простите, у нас что-то сломалось, но мы скоро это починим!');
    }
}