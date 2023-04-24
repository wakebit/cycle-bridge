<?php

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Migrations\Config\MigrationConfig;
use Psr\Container\ContainerInterface;
use Spiral\Tokenizer\Config\TokenizerConfig;
use Wakebit\CycleBridge\Schema\Config\SchemaConfig;

use function DI\create;

/**
 * @psalm-suppress UndefinedClass
 * @psalm-suppress MixedArgument
 */
return [
    'database' => static function (ContainerInterface $container): DatabaseConfig {
        return new DatabaseConfig([
            'default' => 'default',

            'databases' => [
                'default' => [
                    'connection' => $container->get('config')['db.connection'],
                ],
            ],

            'connections' => [
                'sqlite' => new \Cycle\Database\Config\SQLiteDriverConfig(
                    connection: new \Cycle\Database\Config\SQLite\MemoryConnectionConfig(),
                    queryCache: true,
                ),

                'mysql' => new \Cycle\Database\Config\MySQLDriverConfig(
                    connection: new \Cycle\Database\Config\MySQL\TcpConnectionConfig(
                        database: $container->get('config')['db.database'],
                        host: $container->get('config')['db.host'],
                        port: 3306,
                        user: $container->get('config')['db.username'],
                        password: $container->get('config')['db.password'],
                    ),
                    queryCache: true,
                ),

                'postgres' => new \Cycle\Database\Config\PostgresDriverConfig(
                    connection: new \Cycle\Database\Config\Postgres\TcpConnectionConfig(
                        database: $container->get('config')['db.database'],
                        host: $container->get('config')['db.host'],
                        port: 5432,
                        user: $container->get('config')['db.username'],
                        password: $container->get('config')['db.password'],
                    ),
                    schema: 'public',
                    queryCache: true,
                ),

                'sqlServer' => new \Cycle\Database\Config\SQLServerDriverConfig(
                    connection: new \Cycle\Database\Config\SQLServer\TcpConnectionConfig(
                        database: $container->get('config')['db.database'],
                        host: $container->get('config')['db.host'],
                        port: 1433,
                        user: $container->get('config')['db.username'],
                        password: $container->get('config')['db.password'],
                    ),
                    queryCache: true,
                ),
            ],
        ]);
    },

    'orm' => [
        'schema' => create(SchemaConfig::class),

        'tokenizer' => static function (): TokenizerConfig {
            return new TokenizerConfig([
                'directories' => [
                    __DIR__ . '/../App/Entity',
                ],

                'exclude' => [
                ],
            ]);
        },
    ],

    'migrations' => static function (ContainerInterface $container): MigrationConfig {
        return new MigrationConfig([
            'directory' => __DIR__ . '/../resources/migrations',
            'table'     => 'migrations',
            'safe'      => filter_var($container->get('config')['debug'], FILTER_VALIDATE_BOOLEAN),
        ]);
    },
];
