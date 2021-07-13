<?php

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

set_error_handler(
    function ($errno, $errstr, $errfile, $errline, array $errcontex) {
        throw new Exception($errstr, 0);
    },
    E_ALL
);

use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\Credentials\ApplicationCredentials;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\Credentials\RootCredentials;
use RC\Infrastructure\Dotenv\EnvironmentDependentEnvFile;
use RC\Infrastructure\Filesystem\DirPath\ExistentFromAbsolutePathString as DirPath;
use RC\Infrastructure\Filesystem\FilePath\ExistentFromAbsolutePathString as FilePath;
use RC\Infrastructure\Http\Request\Inbound\DefaultInbound;
use RC\Infrastructure\Setup\Database\Recreate;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\Port\FromString;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\DatabaseName\SpecifiedDatabaseName;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\Host\FromString as Host;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\Credentials\DefaultCredentials;

if (!file_exists(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . '.env.dev')) {
    throw new Exception('It seems you run this file outside of dev environment. You can not do that.');
}

(new EnvironmentDependentEnvFile(
    new DirPath(dirname(dirname(__DIR__))),
    new DefaultInbound()
))
    ->load();

$r1 =
    (new Recreate(
        new DirPath(dirname(dirname(__DIR__))),
        new Host(getenv('DB_HOST')),
        new FromString(getenv('DB_PORT')),
        new SpecifiedDatabaseName(getenv('DB_NAME')),
        new RootCredentials(),
        new ApplicationCredentials(),
        new FilePath(sprintf('%s/../rc/setup/createTables.sql', dirname(__FILE__))),
        new FilePath(sprintf('%s/../rc.php', dirname(__FILE__)))
    ))
        ->value();

if (!$r1->isSuccessful()) {
    var_dump($r1->error()->context());
    die();
}

die('Go have some food, dirt.');

$r2 =
    (new SeedOrdersDelivery(
        new FromString(dirname(dirname(__DIR__))),
        new FromString(getenv('DB_PORT'))
    ))
        ->value();

if (!$r2->isSuccessful()) {
    var_dump($r2->error()->value());
    die();
}

exec(
    sprintf(
        '%s/vendor/bin/phinx migrate -c %s/migrations/%s.php 2>&1',
        (new FromString(dirname(dirname(__DIR__))))->value(),
        (new FromString(dirname(dirname(__DIR__))))->value(),
        'rc'
    ),
    $output,
    $status
);
if ($status !== 0) {
    var_dump($output);
    die();
}

$r3 =
    (new Recreate(
        new FromString(dirname(dirname(__DIR__))),
        new Host(getenv('HISTORY_DB_HOST')),
        new FromString((int) getenv('HISTORY_DB_PORT')),
        new SpecifiedDatabaseName(getenv('HISTORY_DB_NAME')),
        new DefaultCredentials(getenv('HISTORY_ROOT_USER'), getenv('HISTORY_ROOT_PASS')),
        'history'
    ))
        ->value();
if (!$r3->isSuccessful()) {
    var_dump($r3->error()->value());
    die();
}

$r5 =
    (new Recreate(
        new FromString(dirname(dirname(__DIR__))),
        new Host(getenv('REPORT_DB_HOST')),
        new FromString((int) getenv('REPORT_DB_PORT')),
        new SpecifiedDatabaseName(getenv('REPORT_DB_NAME')),
        new DefaultCredentials(getenv('REPORT_ROOT_USER'), getenv('REPORT_ROOT_PASS')),
        'report'
    ))
        ->value();
if (!$r5->isSuccessful()) {
    var_dump($r5->error()->value());
    die();
}

(new DevFixtures(
    new FromString(dirname(dirname(__DIR__))),
    new Host(getenv('DB_HOST')),
    new FromString((int) getenv('DB_PORT')),
    new SpecifiedDatabaseName(getenv('DB_NAME')),
    new DefaultCredentials(getenv('DB_ROOT_USER'), getenv('DB_ROOT_PASS'))
))
    ->run();
