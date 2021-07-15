<?php

declare(strict_types=1);

namespace RC\Domain\Infrastructure\Setup\Database;

use Ramsey\Uuid\Uuid;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use RC\Infrastructure\Uuid\RandomUUID;

class Seed
{
    private $connection;

    public function __construct(OpenConnection $connection)
    {
        $this->connection = $connection;
    }

    public function value(): ImpureValue
    {
        $insertInSampleTableResponse = $this->insertInSampleTable();
        if (!$insertInSampleTableResponse->isSuccessful()) {
            return $insertInSampleTableResponse;
        }

        $addGorgonzolaBot = $this->addGorgonzolaBot();
        if (!$addGorgonzolaBot->isSuccessful()) {
            return $addGorgonzolaBot;
        }

        $addAnalysisParadysisGroup = $this->addAnalysisParadysisGroup();
        if (!$addAnalysisParadysisGroup->isSuccessful()) {
            return $addAnalysisParadysisGroup;
        }

        return new Successful(new Emptie());
    }

    private function insertInSampleTable()
    {
        return
            (new SingleMutating(
                'insert into sample_table values (?, ?)',
                [(new RandomUUID())->value(), 'vasya'],
                $this->connection
            ))
                ->response();
    }

    private function addGorgonzolaBot()
    {
        return
            (new SingleMutating(
                'insert into bot values (?, ?, \'false\', ?)',
                ['1f6d0fd5-3179-47fb-b92d-f6bec4e8f016', '1884532101:AAGUQlhCa87lAZNhMws9vKCpjrDihcmJRK4', '@gorgonzola_sandwich_bot'],
                $this->connection
            ))
                ->response();
    }

    private function addAnalysisParadysisGroup()
    {
        return
            (new SingleMutating(
                'insert into "group" values (?, ?, ?)',
                [Uuid::uuid4()->toString(), '1f6d0fd5-3179-47fb-b92d-f6bec4e8f016', 'Analysis Paradysis'],
                $this->connection
            ))
                ->response();
    }
}