# Cycle ORM bridge
This package provides a set of Symfony commands, classes for integration [Cycle ORM](https://cycle-orm.dev) with any framework. We are supporting integration both versions of the ORM.
If you want to integrate this to Laravel we already have a [separated package](https://github.com/wakebit/laravel-cycle) which uses this bridge.

## Requirements
* PHP >= 8.0
* Cycle ORM 1 or 2 (branches `v1.x` and `v2.x` accordingly)

## Installation
1. Install the package via composer:
```bash
composer require wakebit/cycle-bridge
```

## Example of usage with PHP-DI
1. Declare config `config/config.php` and fill database credentials:
```php
<?php

declare(strict_types=1);

use function DI\env;

return [
    // Environment variables
    'debug'         => env('APP_DEBUG', 'true'),

    // Database
    'db.connection' => env('DB_CONNECTION', 'mysql'),
    'db.host'       => env('DB_HOST', 'localhost'),
    'db.database'   => env('DB_DATABASE', ''),
    'db.username'   => env('DB_USERNAME', ''),
    'db.password'   => env('DB_PASSWORD', ''),
];
```

2. Declare ORM config `config/cycle.php`. Dont forget to set up correct paths to entities path, migrations path:
```php
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
        'schema' => static function (): SchemaConfig {
            return new SchemaConfig();
        },

        'tokenizer' => static function (): TokenizerConfig {
            return new TokenizerConfig([
                'directories' => [
                    __DIR__ . '/../src/Entity',
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
```

3. Declare dependencies for PHP-DI container `config/container.php`:
```php
<?php

declare(strict_types=1);

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseManager;
use Cycle\Database\DatabaseProviderInterface;
use Cycle\Migrations\Config\MigrationConfig;
use Cycle\Migrations\FileRepository;
use Cycle\Migrations\RepositoryInterface;
use Cycle\ORM\EntityManager;
use Cycle\ORM\EntityManagerInterface;
use Cycle\ORM\Factory;
use Cycle\ORM\FactoryInterface;
use Cycle\ORM\ORM;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\SchemaInterface;
use Psr\Container\ContainerInterface;
use Spiral\Tokenizer\ClassesInterface;
use Spiral\Tokenizer\ClassLocator;
use Spiral\Tokenizer\Config\TokenizerConfig;
use Spiral\Tokenizer\Tokenizer;
use Wakebit\CycleBridge\Contracts\Schema\CacheManagerInterface;
use Wakebit\CycleBridge\Contracts\Schema\CompilerInterface;
use Wakebit\CycleBridge\Contracts\Schema\GeneratorQueueInterface;
use Wakebit\CycleBridge\Schema\CacheManager;
use Wakebit\CycleBridge\Schema\Compiler;
use Wakebit\CycleBridge\Schema\Config\SchemaConfig;
use Wakebit\CycleBridge\Schema\GeneratorQueue;
use Wakebit\CycleBridge\Schema\SchemaFactory;
use function DI\autowire;
use function DI\factory;
use function DI\get;

return [
    'config'                          => require 'config.php',
    'cycle'                           => require 'cycle.php',

    DatabaseConfig::class             => static function (ContainerInterface $container): DatabaseConfig {
        return $container->get('cycle')['database'];
    },

    SchemaConfig::class               => static function (ContainerInterface $container): SchemaConfig {
        return $container->get('cycle')['orm']['schema'];
    },

    TokenizerConfig::class            => static function (ContainerInterface $container): TokenizerConfig {
        return $container->get('cycle')['orm']['tokenizer'];
    },

    MigrationConfig::class            => static function (ContainerInterface $container): MigrationConfig {
        return $container->get('cycle')['migrations'];
    },

    DatabaseProviderInterface::class => autowire(DatabaseManager::class),
    DatabaseInterface::class         => static function (ContainerInterface $container): DatabaseInterface {
        return $container->get(DatabaseProviderInterface::class)->database();
    },
    DatabaseManager::class           => get(DatabaseProviderInterface::class),

    ClassLocator::class             => get(ClassesInterface::class),
    ClassesInterface::class         => static function (ContainerInterface $container): ClassesInterface {
        return $container->get(Tokenizer::class)->classLocator();
    },

    FactoryInterface::class         => autowire(Factory::class),
    CacheManagerInterface::class    => static function (): CacheManagerInterface {
        // Here you need to pass PSR-16 compatible cache pool. See example with cache file below.
        // Packages: league/flysystem, cache/filesystem-adapter
        $filesystemAdapter = new \League\Flysystem\Adapter\Local(__DIR__ . '/../var/cache');
        $filesystem = new \League\Flysystem\Filesystem($filesystemAdapter);
        $pool = new \Cache\Adapter\Filesystem\FilesystemCachePool($filesystem);

        return new CacheManager($pool);
    },

    GeneratorQueueInterface::class  => autowire(GeneratorQueue::class),
    CompilerInterface::class        => autowire(Compiler::class),
    SchemaInterface::class          => factory([SchemaFactory::class, 'create']),
    ORMInterface::class => static function (ContainerInterface $container): ORMInterface {
        return new ORM($container->get(FactoryInterface::class), $container->get(SchemaInterface::class));
    },
    EntityManagerInterface::class   => autowire(EntityManager::class),
    RepositoryInterface::class      => autowire(FileRepository::class),
];
```

4. Now, you need to load a dependencies array created in the step above to PHP-DI. After you are free to use dependencies, write your code.

Let's look at quick example. Define entity:
```php
<?php

declare(strict_types=1);

namespace App\Entity;

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;

#[Entity]
class User
{
    #[Column(type: 'primary')]
    private int $id;

    #[Column(type: 'string')]
    private string $name;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
```

You can take DBAL, ORM and EntityManager from the container. Quick example of usage:

```php
<?php

declare(strict_types=1);

namespace App;

use Cycle\Database\DatabaseProviderInterface;
use Cycle\ORM\EntityManagerInterface;
use Cycle\ORM\ORMInterface;

final class SomeClass
{
    public function __construct(
        private DatabaseProviderInterface $dbal,
        private EntityManagerInterface $em,
        private ORMInterface $orm,
    ) {
    }
    
    public function __invoke()
    {
        // DBAL
        $tables = $this->dbal->database()->getTables();
        $tableNames = array_map(fn (\Cycle\Database\TableInterface $table): string => $table->getName(), $tables);
        dump($tableNames);
        
        // Create, modify, delete entities using Transaction
        $user = new \App\Entity\User();
        $user->setName("Hello World");
        $this->em->persist($user);
        $this->em->run();
        dump($user);
        
        // ORM
        $repository = $this->orm->getRepository(\App\Entity\User::class);
        $users = $repository->findAll();
        dump($users);
        
        $user = $repository->findByPK(1);
        dump($user);
    }
}
```
See more on [the official Cycle ORM documentation](https://cycle-orm.dev/docs/readme/1.x/en).

## Console commands
### Working with ORM schema
| Command                | Description                                                                    | Options                                                                      | Symfony command class FQN                                           |
|------------------------|--------------------------------------------------------------------------------|:-----------------------------------------------------------------------------|---------------------------------------------------------------------|
| `cycle:schema:migrate` | Generate ORM schema migrations                                                 | - `--run`: Automatically run generated migration.<br>- `-v`: Verbose output. | `\Wakebit\CycleBridge\Console\Command\Schema\MigrateCommand::class` |
| `cycle:schema:cache`   | Compile and cache ORM schema                                                   |                                                                              | `\Wakebit\CycleBridge\Console\Command\Schema\CacheCommand::class`   |
| `cycle:schema:clear`   | Clear cached schema (schema will be generated every request now)               |                                                                              | `\Wakebit\CycleBridge\Console\Command\Schema\ClearCommand::class`   |
| `cycle:schema:sync`    | Sync ORM schema with database without intermediate migration (risk operation!) |                                                                              | `\Wakebit\CycleBridge\Console\Command\Schema\SyncCommand::class`    |
| `cycle:schema:render`  | Render available CycleORM schemas.                                             | - `--no-color`: Display output without colors.                               | `\Wakebit\CycleBridge\Console\Command\Schema\RenderCommand::class`  |


### Database migrations
| Command                  | Description                                        | Options                                                                                                       | Symfony command class FQN                                             |
|--------------------------|----------------------------------------------------|:--------------------------------------------------------------------------------------------------------------|-----------------------------------------------------------------------|
| `cycle:migrate:init`     | Initialize migrator: create a table for migrations |                                                                                                               | `\Wakebit\CycleBridge\Console\Command\Migrate\InitCommand::class`     |
| `cycle:migrate`          | Run all outstanding migrations                     | - `--one`: Execute only one (first) migration.<br>- `--force`: Force the operation to run when in production. | `\Wakebit\CycleBridge\Console\Command\Migrate\MigrateCommand::class`  |
| `cycle:migrate:rollback` | Rollback the last migration                        | - `--all`: Rollback all executed migrations.<br>- `--force`: Force the operation to run when in production.   | `\Wakebit\CycleBridge\Console\Command\Migrate\RollbackCommand::class` |
| `cycle:migrate:replay`   | Replay (down, up) one or multiple migrations.      | - `--all`: Replay all migrations.                                                                             | `\Wakebit\CycleBridge\Console\Command\Migrate\ReplayCommand::class`   |
| `cycle:migrate:status`   | Get a list of available migrations                 |                                                                                                               | `\Wakebit\CycleBridge\Console\Command\Migrate\StatusCommand::class`   |

### Database commands
| Command                  | Description                                                     | Options                       | Symfony command class FQN                                           |
|--------------------------|-----------------------------------------------------------------|:------------------------------|---------------------------------------------------------------------|
| `cycle:db:list`          | Get list of available databases, their tables and records count | - `--database`: Database name | `\Wakebit\CycleBridge\Console\Command\Database\ListCommand::class`  |
| `cycle:db:table <table>` | Describe table schema of specific database                      | - `--database`: Database name | `\Wakebit\CycleBridge\Console\Command\Database\TableCommand::class` |

## Writing functional tests
If you are using memory database (SQLite) you can just run migrations in the `setUp` method of the your test calling the console command `cycle:migrate`.
For another databases follow [this instruction](https://cycle-orm.dev/docs/advanced-testing/1.x/en) and drop all tables in the `tearDown` method.

## Advanced
### Manually defined ORM schema
If you want to use a manually defined ORM schema you can define it in the `cycle.php` `orm.schema.map` config key:
```php
use Wakebit\CycleBridge\Schema\Config\SchemaConfig;

return [
    // ...
    'orm' => [
        'schema' => static function (): SchemaConfig {
            return new SchemaConfig([
                'map' => require __DIR__ . '/../orm_schema.php',
            ]);
        },
    ]
    // ...
]
```
Manually defined schema should be presented as array. It will be passed to `\Cycle\ORM\Schema` constructor. See more [here](https://cycle-orm.dev/docs/advanced-manual/1.x/en).

### Custom schema compilation pipeline
You can redefine the ORM schema compilation generators in the `cycle.php` `orm.schema.generators` config key:
```php
use Wakebit\CycleBridge\Schema\Config\SchemaConfig;

return [
    // ...
    'orm' => [
        'schema' => static function (): SchemaConfig {
            return new SchemaConfig([
                'generators' => [
                    'index' => [],
                    'render' => [
                        \Cycle\Schema\Generator\ResetTables::class,         // re-declared table schemas (remove columns)
                        \Cycle\Schema\Generator\GenerateRelations::class,   // generate entity relations
                        \Cycle\Schema\Generator\ValidateEntities::class,    // make sure all entity schemas are correct
                        \Cycle\Schema\Generator\RenderTables::class,        // declare table schemas
                        \Cycle\Schema\Generator\RenderRelations::class,     // declare relation keys and indexes
                    ],
                    'postprocess' => [
                        \Cycle\Schema\Generator\GenerateTypecast::class,    // typecast non string columns
                    ],
                ]
            ]);
        },
    ]
    // ...
]
```
Classes will be resolved by DI container. Default pipeline you can see [here](https://github.com/wakebit/cycle-bridge/blob/v1.x/src/Schema/Config/SchemaConfig.php#L32).

### Default collection factory
You can specify predefined or your own collection factory in container definition:
```php
FactoryInterface::class => static function (ContainerInterface $container): FactoryInterface {
    return new \Cycle\ORM\Factory(
        dbal: $container->get(DatabaseProviderInterface::class),
        defaultCollectionFactory: new \Cycle\ORM\Collection\DoctrineCollectionFactory(),
    );
},
```

# Credits
- [Cycle ORM](https://github.com/cycle), PHP DataMapper ORM and Data Modelling Engine by SpiralScout.
- [Spiral Scout](https://github.com/spiral), author of the Cycle ORM.
- [Spiral Framework Cycle Bridge](https://github.com/spiral/cycle-bridge/) for code samples, example of usage.

