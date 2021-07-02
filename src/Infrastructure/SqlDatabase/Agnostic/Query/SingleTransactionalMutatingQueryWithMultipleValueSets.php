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

class SingleTransactionalMutatingQueryWithMultipleValueSets implements Query
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

        $statement = null;
        try {
            $statement =
                $dbh->prepare(
                    (new QueryStringWithCorrectQuestionMarksQuantityInClausesWithArrays(
                        $this->queryString,
                        array_values($this->values)[0]
                    ))
                        ->value()
                );
            $dbh->beginTransaction();

            foreach ($this->values as $value) {
                $result = $statement->execute((new FlatValues($value))->value());
                if ($result === false) {
                    return new FailedImpureValue(new AlarmDeclineWithDefaultUserMessage($statement->errorInfo()[2], $statement->errorInfo()));
                }
            }

            $statement->closeCursor();
            $statement = null;

            $dbh->commit();
        } catch (Throwable $e) {
            $dbh->rollBack();

            if (!is_null($statement)) {
                $statement->closeCursor();
                $statement = null;
            }

            return new FailedImpureValue(new AlarmDeclineWithDefaultUserMessage($e->getMessage(), $e->getTrace()));
        }

        return new Successful(new Emptie());
    }
}
