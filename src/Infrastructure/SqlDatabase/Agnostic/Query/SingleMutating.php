<?php

declare(strict_types=1);

namespace RC\Infrastructure\SqlDatabase\Agnostic\Query;

use RC\Infrastructure\ImpureInteractions\Error\AlarmDeclineWithDefaultUserMessage;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Failed as FailedImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query;
use Throwable;

class SingleMutating implements Query
{
    private $queryString;
    private $values;
    private $connection;

    public function __construct(string $queryString, array $values, OpenConnection $connection)
    {
        $this->queryString = $queryString;
        $this->values = $values;
        $this->connection = $connection;
    }

    public function response(): ImpureValue
    {
        try {
            $dbh = $this->connection->value();
        } catch (Throwable $e) {
            return new FailedImpureValue(new AlarmDeclineWithDefaultUserMessage($e->getMessage(), $e->getTrace()));
        }

        try {
            $statement =
                $dbh->prepare(
                    (new QueryStringWithCorrectQuestionMarksQuantityInClausesWithArrays(
                        $this->queryString,
                        $this->values
                    ))
                        ->value()
                );

            $result = $statement->execute((new FlatValues($this->values))->value());
        } catch (Throwable $e) {
            return new FailedImpureValue(new AlarmDeclineWithDefaultUserMessage($e->getMessage(), $e->getTrace()));
        }

        if ($result === false) {
            return new FailedImpureValue(new AlarmDeclineWithDefaultUserMessage($statement->errorInfo()[2], $statement->errorInfo()));
        }

        $statement->closeCursor();
        $statement = null;

        return new Successful(new Emptie());
    }
}
