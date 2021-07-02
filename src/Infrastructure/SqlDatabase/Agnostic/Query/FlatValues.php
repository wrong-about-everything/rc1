<?php

declare(strict_types=1);

namespace RC\Infrastructure\SqlDatabase\Agnostic\Query;

class FlatValues
{
    private $values;

    public function __construct(array $values)
    {
        $this->values = $values;
    }

    public function value(): array
    {
        return
            array_reduce(
                $this->values,
                function (array $flat, $e) {
                    return
                        is_array($e)
                            ? array_merge($flat, (new FlatValues($e))->value())
                            : array_merge($flat, [$e])
                        ;
                },
                []
            );
    }
}