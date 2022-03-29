<?php

declare(strict_types=1);

namespace Wakebit\CycleBridge\Tests\Console\Command\Migrate;

use Spiral\Database\DatabaseManager;
use Symfony\Component\Console\Tester\CommandTester;
use Wakebit\CycleBridge\Console\Command\Migrate\InitCommand;
use Wakebit\CycleBridge\Tests\TestCase;

final class InitCommandTest extends TestCase
{
    public function testConsoleCommand(): void
    {
        $dbal = $this->container->get(DatabaseManager::class);

        $this->assertCount(0, $dbal->database()->getTables());

        $commandTester = new CommandTester($this->container->get(InitCommand::class));
        $exitCode = $commandTester->execute([]);

        $this->assertSame(0, $exitCode);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Migrations table were successfully created.', $output);

        $tables = $dbal->database()->getTables();
        $this->assertCount(1, $tables);
        $this->assertSame('migrations', $tables[0]->getName());
    }
}
