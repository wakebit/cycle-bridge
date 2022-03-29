<?php

declare(strict_types=1);

use Cycle\ORM\Factory;
use Cycle\ORM\FactoryInterface;
use Cycle\ORM\ORM;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Transaction;
use Cycle\ORM\TransactionInterface;
use Psr\Container\ContainerInterface;
use Spiral\Database\Config\DatabaseConfig;
use Spiral\Database\DatabaseInterface;
use Spiral\Database\DatabaseManager;
use Spiral\Database\DatabaseProviderInterface;
use Spiral\Migrations\Config\MigrationConfig;
use Spiral\Migrations\FileRepository;
use Spiral\Migrations\RepositoryInterface;
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
    'cycle'                           => require __DIR__ . '/cycle.php',

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
    DatabaseManager::class           => get(DatabaseProviderInterface::class), // https://github.com/cycle/migrations/pull/24

    ClassLocator::class             => get(ClassesInterface::class),
    ClassesInterface::class         => static function (ContainerInterface $container): ClassesInterface {
        return $container->get(Tokenizer::class)->classLocator();
    },

    FactoryInterface::class         => autowire(Factory::class),
    CacheManagerInterface::class    => static function (): CacheManagerInterface {
        $filesystemAdapter = new \League\Flysystem\Adapter\Local(__DIR__ . '/../var/cache');
        $filesystem = new \League\Flysystem\Filesystem($filesystemAdapter);
        $pool = new \Cache\Adapter\Filesystem\FilesystemCachePool($filesystem);

        return new CacheManager($pool);
    },

    GeneratorQueueInterface::class  => autowire(GeneratorQueue::class),
    CompilerInterface::class        => autowire(Compiler::class),
    SchemaInterface::class          => factory([SchemaFactory::class, 'create']),
    ORMInterface::class             => autowire(ORM::class),
    TransactionInterface::class     => autowire(Transaction::class),
    RepositoryInterface::class      => autowire(FileRepository::class),
];
