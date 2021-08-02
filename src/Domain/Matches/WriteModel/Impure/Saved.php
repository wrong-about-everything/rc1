<?php

declare(strict_types=1);

namespace RC\Domain\Matches\WriteModel\Impure;

use Ramsey\Uuid\Uuid;
use RC\Domain\Matches\ReadModel\Impure\Matches;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutatingQueryWithMultipleValueSets;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\TransactionalQueryFromMultipleQueries;

class Saved implements Matches
{
    private $matches;
    private $connection;
    private $cached;

    public function __construct(Matches $matches, OpenConnection $connection)
    {
        $this->matches = $matches;
        $this->connection = $connection;
        $this->cached = null;
    }

    public function value(): ImpureValue
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doValue();
        }

        return $this->cached;
    }

    private function doValue()
    {
        if (!$this->matches->value()->isSuccessful()) {
            return $this->matches->value();
        }

        return
            (new TransactionalQueryFromMultipleQueries(
                [
                    new SingleMutatingQueryWithMultipleValueSets(
                        'insert into meeting_round_pair values (?, ?, ?)',
                        array_reduce(
                            $this->matches->value()->pure()->raw()['matches'],
                            function (array $carry, array $pair) {
                                $carry[] = [Uuid::uuid4()->toString(), $pair[0], $pair[1]];
                                $carry[] = [Uuid::uuid4()->toString(), $pair[1], $pair[0]];
                                return $carry;
                            },
                            []
                        ),
                        $this->connection
                    ),
                    new SingleMutatingQueryWithMultipleValueSets(
                        'insert into meeting_round_dropout values (?, ?)',
                        array_reduce(
                            $this->matches->value()->pure()->raw()['dropouts'],
                            function (array $carry, $dropout) {
                                $carry[] = [Uuid::uuid4()->toString(), $dropout];
                                return $carry;
                            },
                            []
                        ),
                        $this->connection
                    )
                ],
                $this->connection
            ))
                ->response();
    }
}