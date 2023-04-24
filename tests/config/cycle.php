<?php

use Psr\Container\ContainerInterface;
use Spiral\Database\Config\DatabaseConfig;
use Spiral\Migrations\Config\MigrationConfig;
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
                'sqlite' => [
                    'driver'  => \Spiral\Database\Driver\SQLite\SQLiteDriver::class,
                    'options' => [
                        'connection' => 'sqlite::memory:',
                        'username'   => '',
                        'password'   => '',
                    ],
                ],
                'mysql' => [
                    'driver'  => \Spiral\Database\Driver\MySQL\MySQLDriver::class,
                    'options' => [
                        'connection' => sprintf(
                            'mysql:host=%s;dbname=%s',
                            $container->get('config')['db.host'],
                            $container->get('config')['db.database']
                        ),
                        'username'   => $container->get('config')['db.username'],
                        'password'   => $container->get('config')['db.password'],
                    ],
                ],
                'postgres'  => [
                    'driver'  => \Spiral\Database\Driver\Postgres\PostgresDriver::class,
                    'options' => [
                        'connection' => sprintf(
                            'pgsql:host=%s;dbname=%s',
                            $container->get('config')['db.host'],
                            $container->get('config')['db.database']
                        ),
                        'username'   => $container->get('config')['db.username'],
                        'password'   => $container->get('config')['db.password'],
                    ],
                ],
                'sqlServer' => [
                    'driver'  => \Spiral\Database\Driver\SQLServer\SQLServerDriver::class,
                    'options' => [
                        'connection' => sprintf(
                            'sqlsrv:Server=%s;Database=%s',
                            $container->get('config')['db.host'],
                            $container->get('config')['db.database']
                        ),
                        'username'   => $container->get('config')['db.username'],
                        'password'   => $container->get('config')['db.password'],
                    ],
                ],
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
