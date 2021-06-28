<?php

declare(strict_types=1);

namespace RC\Infrastructure\Logging\LogItem;

use Meringue\Timeline\Point\Now;
use RC\Infrastructure\Logging\LogItem;
use RC\Infrastructure\Logging\Severity\Info;

class InformationMessage implements LogItem
{
    private $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    public function value(): array
    {
        return [
            'timestamp' => (new Now())->value(),
            'severity' => (new Info())->value(),
            'message' => $this->message,
        ];
    }
}
