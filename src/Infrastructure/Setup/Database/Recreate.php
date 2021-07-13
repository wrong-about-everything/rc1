<?php

declare(strict_types=1);

namespace RC\Infrastructure\Setup\Database;

use Exception;
use RC\Infrastructure\Filesystem\DirPath;
use RC\Infrastructure\Filesystem\FileContents\FromFilePath;
use RC\Infrastructure\Filesystem\FilePath;
use RC\Infrastructure\ImpureInteractions\Error\SilentDeclineWithDefaultUserMessage;
use RC\Infrastructure\ImpureInteractions\Error\SilentDeclineWithDefaultUserMessageFromPdo;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Failed;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\Credentials;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\DatabaseName;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\DefaultConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\Host;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\Port;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\ConnectionToSystemDatabase;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\Credentials\RootCredentials;
use PDO;

class Recreate
{
    private $projectRootPath;
    private $host;
    private $port;
    private $databaseName;
    private $rootCredentials;
    private $applicationCredentials;
    private $createTablesFile;
    private $migrationConfigFilename;

    public function __construct(
        DirPath $projectRoot,
        Host $host,
        Port $port,
        DatabaseName $databaseName,
        Credentials $rootCredentials,
        Credentials $applicationCredentials,
        FilePath $createTablesFile,
        FilePath $migrationConfigFilename
    )
    {
        if (!$createTablesFile->exists()) {
            throw new Exception('File with tables does not exist.');
        }

        $this->projectRootPath = $projectRoot;
        $this->host = $host;
        $this->port = $port;
        $this->databaseName = $databaseName;
        $this->rootCredentials = $rootCredentials;
        $this->applicationCredentials = $applicationCredentials;
        $this->createTablesFile = $createTablesFile;
        $this->migrationConfigFilename = $migrationConfigFilename;
    }

    public function value(): ImpureValue
    {
        list($status1, $output1) = $this->dropDb();
        if ($status1 !== 0) {
            return new Failed(new SilentDeclineWithDefaultUserMessage((string) $status1, $output1));
        }

        $rootUserConnectionToSystemDatabase =
            (new ConnectionToSystemDatabase(
                $this->port,
                $this->host,
                $this->rootCredentials
            ))
                ->value();

        $r1 = $this->dropUser($rootUserConnectionToSystemDatabase);
        if ($r1 === false) {
            return new Failed(new SilentDeclineWithDefaultUserMessageFromPdo($rootUserConnectionToSystemDatabase));
        }

        list($status2, $output2) = $this->createDb();
        if ($status2 !== 0) {
            return new Failed(new SilentDeclineWithDefaultUserMessage((string) $status2, $output2));
        }

        $r2 = $this->createUser($rootUserConnectionToSystemDatabase);
        if ($r2 === false) {
            return new Failed(new SilentDeclineWithDefaultUserMessageFromPdo($rootUserConnectionToSystemDatabase));
        }

        $rootConnectionToApplicationDatabase =
            (new DefaultConnection(
                $this->host,
                $this->port,
                $this->databaseName,
                new RootCredentials()
            ))
                ->value();

        $r3 = $this->createTables($rootConnectionToApplicationDatabase);
        if ($r3 === false) {
            return new Failed(new SilentDeclineWithDefaultUserMessageFromPdo($rootUserConnectionToSystemDatabase));
        }

//        list($status3, $output3) = $this->runMigrations();
//        if ($status3 !== 0) {
//            return new Failed(new SilentDeclineWithDefaultUserMessage((string) $status3, $output3));
//        }

        return new Successful(new Emptie());
    }

    private function dropDb()
    {
        exec(
            sprintf(
                'PGPASSWORD=%s dropdb --if-exists -p %s -h %s -U %s %s 2>&1',
                (new RootCredentials())->password(),
                $this->port->value(),
                $this->host->value(),
                (new RootCredentials())->username(),
                $this->databaseName->value()
            ),
            $output1,
            $status1
        );

        return [$status1, $output1];
    }

    private function dropUser(PDO $rootUserPdo)
    {
        return
            $rootUserPdo
                ->exec(
                    sprintf(
                        'drop role if exists %s;',
                        $this->applicationCredentials->username()
                    )
                );
    }

    private function createDb()
    {
        exec(
            sprintf(
                'PGPASSWORD=%s createdb -p %s -h %s -U %s %s 2>&1',
                (new RootCredentials())->password(),
                $this->port->value(),
                $this->host->value(),
                (new RootCredentials())->username(),
                $this->databaseName->value()
            ),
            $output2,
            $status2
        );

        return [$status2, $output2];
    }

    private function createUser(PDO $rootUserPdo)
    {
        return
            $rootUserPdo
                ->exec(
<<<q
create user {$this->applicationCredentials->username()} with encrypted password '{$this->applicationCredentials->password()}';
q
                );
    }

    private function createTables(PDO $rootUserPdo)
    {
        return
            $rootUserPdo
                ->exec(
                    (new FromFilePath($this->createTablesFile))->value()->pure()->raw()
                );
    }

    private function runMigrations()
    {
        exec(
            sprintf(
                '%s/vendor/bin/phinx migrate -c %s/migrations/%s.php 2>&1',
                $this->projectRootPath->value(),
                $this->projectRootPath->value(),
                $this->migrationConfigFilename
            ),
            $output3,
            $status3
        );

        return [$status3, $output3];
    }
}

