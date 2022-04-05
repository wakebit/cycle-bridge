<?php

declare(strict_types=1);

namespace Wakebit\CycleBridge\Tests\Console\Command\Schema;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Wakebit\CycleBridge\Console\Command\Schema\ClearCommand;
use Wakebit\CycleBridge\Contracts\Schema\CacheManagerInterface;
use Wakebit\CycleBridge\Tests\TestCase;

final class ClearCommandTest extends TestCase
{
    public function testClear(): void
    {
        $cache = $this->createMock(CacheManagerInterface::class);
        $cache->expects($this->once())->method('clear')->with();
        $this->container->set(CacheManagerInterface::class, $cache);

        $commandTester = new CommandTester($this->container->get(ClearCommand::class));
        $exitCode = $commandTester->execute([]);
        $output = $commandTester->getDisplay();
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('ORM schema cache cleared!', $output);
    }
}
