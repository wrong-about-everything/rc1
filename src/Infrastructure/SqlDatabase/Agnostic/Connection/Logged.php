<?php

declare(strict_types=1);

namespace RC\Infrastructure\SqlDatabase\Agnostic\Connection;

use Meringue\FormattedInterval\TotalSecondsWithMilliseconds;
use Meringue\ISO8601Interval\WithFixedStartDateTime\FromRange;
use Meringue\Timeline\Point\Now;
use PDO;
use RC\Infrastructure\Logging\LogItem\FromThrowable;
use RC\Infrastructure\Logging\LogItem\InformationMessage;
use RC\Infrastructure\Logging\Logs;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use Throwable;

class Logged implements OpenConnection
{
    private $connection;
    private $logs;

    public function __construct(OpenConnection $connection, Logs $logs)
    {
        $this->connection = $connection;
        $this->logs = $logs;
    }

    public function value(): PDO
    {
        $this->logs->add(new InformationMessage('Obtaining db connect'));

        $everyNow = new Now();
        $r = $this->connection->value();
        $andThen = new Now();

        if ($andThen->equalsTo($everyNow)) {
            $this->logs->add(new InformationMessage('Connection obtaining took just a tiniest bit of time'));
        } else {
            try {
                $this->logs
                    ->add(
                        new InformationMessage(
                            sprintf(
                                'Connection obtaining took %s seconds',
                                (new TotalSecondsWithMilliseconds(new FromRange($everyNow, $andThen)))->value()
                            )
                        )
                    );
            } catch (Throwable $e) {
                $this->logs->add(new FromThrowable($e));
            }
        }

        return $r;
    }
}
