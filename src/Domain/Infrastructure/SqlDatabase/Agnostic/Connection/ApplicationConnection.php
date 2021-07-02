<?php

declare(strict_types=1);

namespace RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection;

use PDO;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\DatabaseName\SpecifiedDatabaseName;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\Host\FromString;
use RC\Infrastructure\SqlDatabase\Concrete\Postgres\Connection\Dsn;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\Port\FromString;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\Credentials\ApplicationCredentials;

class ApplicationConnection implements OpenConnection
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
                        new FromString(getenv('DB_HOST')),
                        new FromString((int) getenv('DB_PORT')),
                        new SpecifiedDatabaseName(getenv('DB_NAME'))
                    ))
                        ->value(),
                    (new ApplicationCredentials())->username(),
                    (new ApplicationCredentials())->password()
                );
        }

        return $this->pdo;
    }
}
