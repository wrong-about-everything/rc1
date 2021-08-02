<?php

declare(strict_types=1);

namespace RC\Tests\Infrastructure\Environment;

use Exception;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use RC\Tests\Infrastructure\Filesystem\DirPath\Tmp;

class Reset
{
    private $connection;

    public function __construct(OpenConnection $connection)
    {
        $this->connection = $connection;
    }

    public function run(): void
    {
        $this->removeTmpContents();
        $this->truncateTables();
    }

    private function removeTmpContents()
    {
        $this->removeDir((new Tmp())->value()->pure()->raw());
    }

    private function removeDir(string $dirPath)
    {
        $dirContents = glob(sprintf('%s/*', $dirPath));
        if ($dirContents === false) {
            throw new Exception(sprintf('Can not read %s contents', $dirPath));
        }

        array_map(
            function (string $fullPath) {
                if (is_dir($fullPath)) {
                    $this->removeDir($fullPath);
                    rmdir($fullPath);
                } else {
                    unlink($fullPath);
                }
            },
            $dirContents
        );
    }

    private function truncateTables()
    {
        $response =
            (new SingleMutating(
                <<<q
truncate
    sample_table,
    bot,
    "group",
    telegram_user,
    bot_user,
    registration_question,
    user_registration_progress,
    meeting_round,
    meeting_round_invitation,
    meeting_round_registration_question,
    user_round_registration_progress,
    meeting_round_participant,
    meeting_round_pair,
    meeting_round_dropout
    
    cascade
q
                ,
                [],
                $this->connection
            ))
                ->response();

        if (!$response->isSuccessful()) {
            throw new Exception($response->error()->logMessage());
        }
    }
}