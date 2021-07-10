<?php

declare(strict_types=1);

namespace RC\Infrastructure\Logging\LogItem;

use Exception;
use Meringue\Timeline\Point\Now;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\Logging\LogItem;
use RC\Infrastructure\Logging\Severity\Error;

/**
 * @todo: Do I need a database result?? Pretty sure I can get away with ImpureValue. That's what database data is anyway.
 * So I probably should use `FromNonSuccessfulImpureValue` class.
 */
class FromDatabaseResult implements LogItem
{
    private $impureValue;
    private $exception;

    public function __construct(ImpureValue $impureValue)
    {
        $this->impureValue = $impureValue;
        $this->exception = new Exception();
    }

    public function value(): array
    {
        return [
            'timestamp' => (new Now())->value(),
            'severity' => (new Error())->value(),
            'message' => $this->impureValue->error()->logMessage(),
            'data' => $this->trace(),
        ];
    }

    private function trace()
    {
        return [
            'trace' => $this->exception->getTraceAsString(),
            'file' => $this->exception->getFile(),
            'line' => $this->exception->getLine(),
        ];
    }
}
