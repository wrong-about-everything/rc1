<?php

declare(strict_types=1);

namespace RC\Domain\UserStory\Body;

use RC\Infrastructure\ImpureInteractions\PureValue;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\UserStory\Body;

class FallbackResponseBody extends Body
{
    public function value(): PureValue
    {
        return new Present('Произошла ужасная ошибка. Если вы её видите, значит мы уже чиним.');
    }
}