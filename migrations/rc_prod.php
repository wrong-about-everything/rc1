<?php

declare(strict_types=1);

namespace PhinxConfig;

use Dotenv\Dotenv as OneAndOnly;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\Credentials\RootCredentials;
use RC\Infrastructure\Filesystem\DirPath\FromAbsolutePathString;
use RC\Infrastructure\Filesystem\DirPath\FromNestedDirectoryNames;
use RC\Infrastructure\Filesystem\Filename\PortableFromString;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\DatabaseName\SpecifiedDatabaseName;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\DefaultConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\Host\FromString;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\Port\FromString as PortFromString;

OneAndOnly::createUnsafeImmutable(
    (new FromNestedDirectoryNames(
        new FromAbsolutePathString(dirname(__DIR__)),
        new PortableFromString('deploy')
    ))
        ->value()->pure()->raw(),
    '.env.prod'
)
    ->load();

$pdo =
    (new DefaultConnection(
        new FromString(getenv('DB_HOST')),
        new PortFromString(getenv('DB_PORT')),
        new SpecifiedDatabaseName(getenv('DB_NAME')),
        new RootCredentials()
    ))
        ->value();

return [
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/rc',
    ],
    'environments' => [
        'default_migration_table' => 'rc_migration',

        'env_file_dependent_environment' => [
            'name' => getenv('DB_NAME'),
            'connection' => $pdo,
        ],
    ],

    'version_order' => 'creation'
];