<?php

declare(strict_types=1);

namespace RC\Infrastructure\Logging\LogItem;

use Meringue\Timeline\Point\Now;
use RC\Infrastructure\Logging\LogItem;
use RC\Infrastructure\Logging\Severity\Error;

class ErrorMessageAndData implements LogItem
{
    private $message;
    private $data;

    public function __construct(string $message, array $data)
    {
        $this->message = $message;
        $this->data = $data;
    }

    public function value(): array
    {
        return [
            'timestamp' => (new Now())->value(),
            'severity' => (new Error())->value(),
            'data' => $this->data,
            'message' => $this->message,
        ];
    }
}
