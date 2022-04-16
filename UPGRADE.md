# Upgrade Guide

## Upgrading from 2.x from 1.x
2.x is using Cycle ORM v2.

Update the following dependency in your composer.json file:

`wakebit/cycle-bridge` to `^2.0`

### Minimum PHP version
PHP 8.0 is now the minimum required version.

### Namespaces
- `spiral/database` is moved to a new repository `cycle/database` so now it has new namespace. To accommodate for these changes you need to replace all namespaces start from `Cycle\Database` with `Cycle\Database`.
- `spiral/migrations` is moved to a new repository `cycle/migrations` so now it has new namespace. To accommodate for these changes you need to replace all namespaces start from `Cycle\Migrations` with `Cycle\Migrations`. Also, don't forget to change extending class in your migration files.

### Config
- Since `cycle/database` v2.0 connection configuration has been changed. You don't need to configure arrays anymore. Use config DTO's instead of. Replace `connections` section's content in the `DatabaseConfig`:
```php
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
```
Read more on [database connection](https://cycle-orm.dev/docs/database-connect/2.x/en) page.

### Entity Manager instead of Transaction
`Cycle\ORM\Transaction` class was marked as deprecated in the Cycle ORM v2. Use `Cycle\ORM\EntityManager` instead. Replace in container definitions this line:
```php
TransactionInterface::class     => autowire(Transaction::class),
```
with:
```php
EntityManagerInterface::class   => autowire(EntityManager::class),
```
Then entity manager can be taken from container.
```php
<?php

/** @var \Cycle\ORM\EntityManagerInterface $em */
$em = $this->container->get(\Cycle\ORM\EntityManagerInterface::class);
$em->persist(...);
$em->run();
```

See usage [here](https://cycle-orm.dev/docs/advanced-entity-manager/2.x/en).

### Default schema compilation pipeline changed
Now it looks so:
```php
[
    GeneratorQueueInterface::GROUP_INDEX => [
        \Cycle\Annotated\Embeddings::class,                 // register embeddable entities
        \Cycle\Annotated\Entities::class,                   // register annotated entities
        \Cycle\Annotated\TableInheritance::class,           // register STI/JTI
        \Cycle\Annotated\MergeColumns::class,               // add @Table column declarations
    ],
    GeneratorQueueInterface::GROUP_RENDER => [
        \Cycle\Schema\Generator\ResetTables::class,         // re-declared table schemas (remove columns)
        \Cycle\Schema\Generator\GenerateRelations::class,   // generate entity relations
        \Cycle\Schema\Generator\GenerateModifiers::class,   // generate changes from schema modifiers
        \Cycle\Schema\Generator\ValidateEntities::class,    // make sure all entity schemas are correct
        \Cycle\Schema\Generator\RenderTables::class,        // declare table schemas
        \Cycle\Schema\Generator\RenderRelations::class,     // declare relation keys and indexes
        \Cycle\Schema\Generator\RenderModifiers::class,     // render all schema modifiers
        \Cycle\Annotated\MergeIndexes::class,               // add @Table column declarations
    ],
    GeneratorQueueInterface::GROUP_POSTPROCESS => [
        \Cycle\Schema\Generator\GenerateTypecast::class,    // typecast non string columns
    ],
];
```

### New commands
- `cycle:schema:render` - Render available schemas.
- `cycle:migrate:replay` - Replay (down, up) one or multiple migrations.

See [readme](README.md#console-commands) for more info.

Also, it may be useful to read the Cycle ORM v2 [upgrading guide](https://cycle-orm.dev/docs/intro-upgrade/2.x/en).
