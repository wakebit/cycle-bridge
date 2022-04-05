<?php

/**
 * Credits: Spiral Cycle Bridge.
 *
 * @see https://github.com/spiral/cycle-bridge
 */

declare(strict_types=1);

namespace Wakebit\CycleBridge\Tests\Console\Command\Schema;

use Cycle\ORM\SchemaInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Wakebit\CycleBridge\Console\Command\Schema\CacheCommand;
use Wakebit\CycleBridge\Contracts\Schema\CacheManagerInterface;
use Wakebit\CycleBridge\TestApp\Entity\Customer;
use Wakebit\CycleBridge\Tests\TestCase;

final class CacheCommandTest extends TestCase
{
    public function testManuallyDefinedSchema(): void
    {
        $schema = [
            'foo' => [
                SchemaInterface::ROLE => 'bar',
            ],
            'john' => [
                SchemaInterface::ROLE => 'bar',
            ],
        ];

        $this->setSchemaConfigValue('map', $schema);

        $commandTester = new CommandTester($this->container->get(CacheCommand::class));
        $exitCode = $commandTester->execute([]);
        $output = $commandTester->getDisplay();
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('ORM schema cached successfully!', $output);

        $outputSchema = $this->container->get(SchemaInterface::class);
        $this->assertTrue($outputSchema->defines('foo'));
        $this->assertTrue($outputSchema->defines('john'));
    }

    public function testGetSchema(): void
    {
        $commandTester = new CommandTester($this->container->get(CacheCommand::class));
        $exitCode = $commandTester->execute([]);
        $output = $commandTester->getDisplay();
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('ORM schema cached successfully!', $output);

        $schema = $this->container->get(SchemaInterface::class);
        $this->assertTrue($schema->defines('customer'));
        $this->assertSame(Customer::class, $schema->define('customer', SchemaInterface::ENTITY));
    }

    public function testGetSchemaFromCache(): void
    {
        $cache = $this->createMock(CacheManagerInterface::class);
        $cache->expects($this->once())->method('write');
        $cache->expects($this->once())->method('isCached')->willReturn(true);
        $cache->expects($this->once())->method('read')->willReturn([]);

        $this->container->set(CacheManagerInterface::class, $cache);

        $commandTester = new CommandTester($this->container->get(CacheCommand::class));
        $exitCode = $commandTester->execute([]);
        $output = $commandTester->getDisplay();
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('ORM schema cached successfully!', $output);

        $schema = $this->container->get(SchemaInterface::class);
        $this->assertFalse($schema->defines('customer'));
    }
}
