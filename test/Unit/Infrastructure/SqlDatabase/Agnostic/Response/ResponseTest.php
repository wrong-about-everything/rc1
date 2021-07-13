<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\SqlDatabase\Agnostic\Response;

use PHPUnit\Framework\TestCase;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query;
use RC\Infrastructure\SqlDatabase\Agnostic\Result;
use RC\Infrastructure\SqlDatabase\Agnostic\Result\Successful;
use RC\Infrastructure\SqlDatabase\Agnostic\Response\Response;
use PDO;

class ResponseTest extends TestCase
{
    public function testSuccessfulResult()
    {
        $this->assertTrue(
            (new Response(
                new class implements Connection
                {
                    public function open(): PDO
                    {
                        return new class extends PDO
                        {
                            public function __construct()
                            {

                            }
                        };
                    }

                    public function close(): void
                    {
                    }
                },
                new class implements Query
                {
                    public function result(PDO $pdo): Result
                    {
                        return new Successful([]);
                    }
                }
            ))
                ->result()
                    ->isSuccessful()
        );
    }
}
