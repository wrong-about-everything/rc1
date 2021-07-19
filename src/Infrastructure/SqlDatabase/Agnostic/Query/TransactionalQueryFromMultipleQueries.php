<?php

declare(strict_types=1);

namespace RC\Infrastructure\SqlDatabase\Agnostic\Query;

use Exception;
use RC\Infrastructure\ImpureInteractions\Error\AlarmDeclineWithDefaultUserMessage;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Combined;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Failed as FailedImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query;
use Throwable;

class TransactionalQueryFromMultipleQueries implements Query
{
    private $queries;
    private $connection;

    public function __construct(array $queries, OpenConnection $connection)
    {
        $this->queries = $queries;
        $this->connection = $connection;
    }

    public function response(): ImpureValue
    {
        try {
            $dbh = $this->connection->value();
        } catch (Throwable $e) {
            return new FailedImpureValue(new AlarmDeclineWithDefaultUserMessage($e->getMessage(), $e->getTrace()));
        }

        $dbh->beginTransaction();

        try {
            $result =
                array_reduce(
                    $this->queries,
                    function (ImpureValue $compositeResult, Query $query) use ($dbh) {
                        $currentResponse = $query->response();
                        if (!$currentResponse->isSuccessful()) {
                            throw new Exception($currentResponse->error()->logMessage()); // short-circuiting workaround
                        }

                        return new Combined($compositeResult, $currentResponse);
                    },
                    new Successful(new Emptie())
                )
            ;
        } catch (Throwable $e) {
            $dbh->rollBack();
            return new FailedImpureValue(new AlarmDeclineWithDefaultUserMessage($e->getMessage(), $e->getTrace()));
        }

        $dbh->commit();

        return $result;
    }
}
