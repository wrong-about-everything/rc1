<?php

declare(strict_types=1);

namespace RC\Domain\FeedbackQuestion\FeedbackQuestionId\Pure;

use RC\Infrastructure\Uuid\FromString as UuidFromString;

class FromString implements FeedbackQuestionId
{
    private $uuid;

    public function __construct(string $uuid)
    {
        $this->uuid = (new UuidFromString($uuid))->value();
    }

    public function value(): string
    {
        return $this->uuid;
    }
}