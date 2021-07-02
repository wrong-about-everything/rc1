<?php

declare(strict_types=1);

namespace PhinxConfig;

use Dotenv\Dotenv;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\Credentials\RootCredentials;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\DatabaseName\SpecifiedDatabaseName;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\DefaultConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\Host\FromString;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\Port\FromString;

if (file_exists(dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env.dev.testing_mode')) {
    (new Dotenv(dirname(__DIR__), '.env.dev.testing_mode'))->load();
} elseif (file_exists(dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env.cicd')) {
    (new Dotenv(dirname(__DIR__), '.env.cicd'))->load();
}

$pdo =
    (new DefaultConnection(
        new FromString(getenv('DB_HOST')),
        new FromString((int) getenv('DB_PORT')),
        new SpecifiedDatabaseName(getenv('DB_NAME')),
        new RootCredentials()
    ))
        ->value();

return [
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/rc',
    ],
    'environments' => [
        'default_migration_table' => 'orders_delivery_migration',

        'env_file_dependent_environment' => [
            'name' => getenv('DB_NAME'),
            'connection' => $pdo,
        ],
    ],

    'version_order' => 'creation'
];
