<?php

declare(strict_types=1);

namespace RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection;

use PDO;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\Credentials\RootCredentials;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\DatabaseName\SpecifiedDatabaseName;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\Host\FromString as HostFromString;
use RC\Infrastructure\SqlDatabase\Concrete\Postgres\Connection\Dsn;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\Port\FromString as PortFromString;

class RootConnection implements OpenConnection
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = null;
    }

    public function value(): PDO
    {
        if (is_null($this->pdo)) {
            $this->pdo =
                new PDO(
                    (new Dsn(
                        new HostFromString(getenv('DB_HOST')),
                        new PortFromString(getenv('DB_PORT')),
                        new SpecifiedDatabaseName(getenv('DB_NAME'))
                    ))
                        ->value(),
                    (new RootCredentials())->username(),
                    (new RootCredentials())->password()
                );
        }

        return $this->pdo;
    }
}
