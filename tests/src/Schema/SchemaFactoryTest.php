<?php

declare(strict_types=1);

namespace Wakebit\CycleBridge\Tests\Schema;

use Cycle\ORM\SchemaInterface;
use Wakebit\CycleBridge\Contracts\Schema\CacheManagerInterface;
use Wakebit\CycleBridge\Schema\SchemaFactory;
use Wakebit\CycleBridge\Tests\TestCase;

final class SchemaFactoryTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->container->get(CacheManagerInterface::class)->clear();

        parent::tearDown();
    }

    public function testTakingFromCache(): void
    {
        $cachingSchema = [
            'foo' => [
                SchemaInterface::ROLE => 'bar',
            ],
            'john' => [
                SchemaInterface::ROLE => 'doe',
            ],
        ];

        $cacheManager = $this->container->get(CacheManagerInterface::class);
        $factory = $this->container->get(SchemaFactory::class);

        $cacheManager->write($cachingSchema);
        $schema = $factory->create();

        $this->assertTrue($schema->defines('foo'));
        $this->assertTrue($schema->defines('john'));
    }

    public function testManuallyDefinedSchema(): void
    {
        $manuallyDefinedSchema = [
            'foo' => [
                SchemaInterface::ROLE => 'bar',
            ],
            'john' => [
                SchemaInterface::ROLE => 'doe',
            ],
        ];

        $this->setSchemaConfigValue('map', $manuallyDefinedSchema);
        $schema = $this->container->get(SchemaFactory::class)->create();

        $this->assertTrue($schema->defines('foo'));
        $this->assertTrue($schema->defines('john'));
    }

    public function testCachingSchemaHasHigherPriorityOverThanManuallyDefinedSchema(): void
    {
        $cachingSchema = [
            'foo' => [
                SchemaInterface::ROLE => 'bar',
            ],
        ];

        $manuallyDefinedSchema = [
            'john' => [
                SchemaInterface::ROLE => 'doe',
            ],
        ];

        $cacheManager = $this->container->get(CacheManagerInterface::class);
        $factory = $this->container->get(SchemaFactory::class);

        $this->setSchemaConfigValue('map', $manuallyDefinedSchema);
        $cacheManager->write($cachingSchema);

        $schema = $factory->create();

        $this->assertTrue($schema->defines('foo'));
        $this->assertFalse($schema->defines('john'));
    }

    public function testRealtimeCompiledSchema(): void
    {
        $factory = $this->container->get(SchemaFactory::class);
        $schema = $factory->create();

        $this->assertTrue($schema->defines('customer'));
        $this->assertTrue($schema->defines('article'));
    }
}
