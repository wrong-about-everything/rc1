<?php

declare(strict_types = 1);

namespace RC\Infrastructure\Http\Request\Url\Query;

use RC\Infrastructure\Http\Request\Url\Query;

class Combined implements Query
{
    private $queries;

    public function __construct(Query ... $queries)
    {
        $this->queries = $queries;
    }

    public function value(): string
    {
        return
            (string) array_reduce(
                $this->queries,
                function (string $carry, Query $query) {
                    return
                        $carry === ''
                            ? $query->value()
                            : $carry . '&' . $query->value()
                        ;
                },
                ''
            );
    }

    public function isSpecified(): bool
    {
        return true;
    }
}
