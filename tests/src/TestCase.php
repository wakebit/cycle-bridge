<?php

declare(strict_types=1);

namespace Wakebit\CycleBridge\Tests;

use Arrayy\Arrayy;
use Cycle\Migrations\Config\MigrationConfig;
use Wakebit\CycleBridge\Schema\Config\SchemaConfig;

/**
 * @psalm-suppress MissingConstructor
 */
class TestCase extends \PHPUnit\Framework\TestCase
{
    protected \DI\Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        /**
         * @var array $definitions
         * @psalm-suppress MissingFile
         */
        $definitions = require __DIR__ . '/../config/container.php';

        $containerBuilder = new \DI\ContainerBuilder();
        $containerBuilder->addDefinitions($definitions);

        $this->container = $containerBuilder->build();
    }

    /**
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     *
     * @psalm-suppress MixedArrayAccess
     * @psalm-suppress MixedArrayAssignment
     */
    protected function setSchemaConfigValue(string $key, mixed $value): void
    {
        /** @var array $cycleConfig */
        $cycleConfig = $this->container->get('cycle');

        /** @var SchemaConfig $schemaConfig */
        $schemaConfig = $cycleConfig['orm']['schema'];
        $schemaConfigAsArray = new Arrayy($schemaConfig->toArray());
        $cycleConfig['orm']['schema'] = new SchemaConfig($schemaConfigAsArray->set($key, $value)->toArray());

        $this->container->set('cycle', $cycleConfig);
    }

    /**
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function setMigrationConfigValue(string $key, mixed $value): void
    {
        /** @var array $cycleConfig */
        $cycleConfig = $this->container->get('cycle');

        /** @var MigrationConfig $migrationConfig */
        $migrationConfig = $cycleConfig['migrations'];
        $migrationConfigAsArray = new Arrayy($migrationConfig->toArray());
        /** @var array{directory?: string|null, table?: string|null, safe?: bool|null} $migrationConfigAsArray */
        $cycleConfig['migrations'] = new MigrationConfig($migrationConfigAsArray->set($key, $value)->toArray());

        $this->container->set('cycle', $cycleConfig);
    }
}
