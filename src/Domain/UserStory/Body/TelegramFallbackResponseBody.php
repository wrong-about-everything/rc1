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
        return new Present('Простите, у нас что-то сломалось. Скорее всего, мы об этом уже знаем, но на всякий случай, напишите пожалуйста об этом в @gorgonzola_support_bot.');
    }
}