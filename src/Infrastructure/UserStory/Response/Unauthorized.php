<?php

declare(strict_types=1);

namespace RC\Infrastructure\UserStory\Response;

use RC\Infrastructure\ImpureInteractions\PureValue;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie;
use RC\Infrastructure\UserStory\Code;
use RC\Infrastructure\UserStory\Code\Unauthorized as UnauthorizedCode;
use RC\Infrastructure\UserStory\Response;

class Unauthorized implements Response
{
    public function isSuccessful(): bool
    {
        return false;
    }

    public function code(): Code
    {
        return new UnauthorizedCode();
    }

    public function headers(): array
    {
        return [];
    }

    public function body(): PureValue
    {
        return new Emptie();
    }
}