<?php

declare(strict_types=1);

namespace RC\Infrastructure\Exception;

use Exception;
use RC\Infrastructure\ImpureInteractions\Error;

/**
 * Just in case:
 * Exceptions are bad for flow control.
 * This class is not intended for it. It is created ONLY for carrying the original context when short-circuiting took place.
 * See `TransactionalQueryFromMultipleQueries` class.
 */
class StateCarrying extends Exception
{
    private $error;

    public function __construct(Error $error)
    {
        parent::__construct($error->logMessage());
        $this->error = $error;
    }

    public function error(): Error
    {
        return $this->error;
    }
}