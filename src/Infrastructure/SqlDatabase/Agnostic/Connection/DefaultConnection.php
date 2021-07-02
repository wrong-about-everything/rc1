<?php

declare(strict_types=1);

namespace RC\Infrastructure\SqlDatabase\Agnostic\Connection;

use PDO;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Concrete\Postgres\Connection\Dsn;

class DefaultConnection implements OpenConnection
{
    private $host;
    private $port;
    private $databaseName;
    private $credentials;
    private $pdo;

    public function __construct(Host $host, Port $port, DatabaseName $databaseName, Credentials $credentials)
    {
        $this->host = $host;
        $this->port = $port;
        $this->databaseName = $databaseName;
        $this->credentials = $credentials;
        $this->pdo = null;
    }

    public function value(): PDO
    {
        if (is_null($this->pdo)) {
            $this->pdo =
                new PDO(
                    (new Dsn($this->host, $this->port, $this->databaseName))->value(),
                    $this->credentials->username(),
                    $this->credentials->password()
                )
            ;
        }

        return $this->pdo;
    }
}
