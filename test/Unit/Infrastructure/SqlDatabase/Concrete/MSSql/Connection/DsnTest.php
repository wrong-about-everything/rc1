<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\SqlDatabase\Concrete\MSSql\Connection;

use PHPUnit\Framework\TestCase;
use RC\Infrastructure\SqlDatabase\Concrete\MSSql\Connection\Dsn;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\Host\FromString;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\Port\SpecifiedPort;

class DsnTest extends TestCase
{
    public function testSuccess()
    {
        $this->assertEquals(
            'sqlsrv:server=localcoast,5432',
            (new Dsn(
                new FromString('localcoast'),
                new SpecifiedPort(5432)
            ))
                ->value()
        );
    }
}
