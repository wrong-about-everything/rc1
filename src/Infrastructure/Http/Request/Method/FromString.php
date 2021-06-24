<?php

declare(strict_types = 1);

namespace RC\Infrastructure\Http\Request\Method;

use Exception;
use RC\Infrastructure\Http\Request\Method;

class FromString extends Method
{
    private $method;

    public function __construct(string $method)
    {
        if ($this->unknownMethod($method)) {
            throw new Exception('Unknown http method');
        }

        $this->method = $method;
    }

    public function value(): string
    {
        return $this->method;
    }

    private function availableMethods()
    {
        return [
            (new Get())->value(),
            (new Post())->value(),
            (new Head())->value(),
            (new Put())->value(),
            (new Delete())->value(),
            (new Options())->value(),
            (new Connect())->value()
        ];
    }

    private function unknownMethod(string $method)
    {
        return !in_array($method, $this->availableMethods());
    }
}