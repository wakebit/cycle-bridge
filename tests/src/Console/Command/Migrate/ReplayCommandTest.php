<?php

declare(strict_types=1);

namespace Wakebit\CycleBridge\Tests\Console\Command\Migrate;

use Cycle\Database\DatabaseInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Wakebit\CycleBridge\Console\Command\Migrate\MigrateCommand;
use Wakebit\CycleBridge\Console\Command\Migrate\ReplayCommand;
use Wakebit\CycleBridge\Tests\Constraints\SeeInOrder;
use Wakebit\CycleBridge\Tests\TestCase;

final class ReplayCommandTest extends TestCase
{
    public function testOneMigration(): void
    {
        $dbal = $this->container->get(DatabaseInterface::class);
        $this->assertSame([], $dbal->getTables());

        $migrateCommand = $this->container->get(MigrateCommand::class);
        $commandTester = new CommandTester($migrateCommand);
        $this->assertSame(Command::SUCCESS, $commandTester->execute([]));
        $this->assertCount(3, $dbal->getTables());

        $replayCommand = $this->container->get(ReplayCommand::class);
        $commandTester = new CommandTester($replayCommand);
        $exitCode = $commandTester->execute([]);
        $realOutput = $commandTester->getDisplay();
        $expectedOutput = [
            'Rolling back executed migration(s)...',
            'Migration 0_default_create_customers was successfully rolled back.',

            'Executing outstanding migration(s)...',
            'Migration 0_default_create_customers was successfully executed.',
        ];

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertCount(3, $dbal->getTables());
        $this->assertThat($expectedOutput, new SeeInOrder($realOutput));
        $this->assertStringNotContainsString('0_default_change_articles_add_description', $realOutput);
        $this->assertStringNotContainsString('0_default_create_articles', $realOutput);
    }

    public function testAllMigrations(): void
    {
        $dbal = $this->container->get(DatabaseInterface::class);
        $this->assertSame([], $dbal->getTables());

        $migrateCommand = $this->container->get(MigrateCommand::class);
        $commandTester = new CommandTester($migrateCommand);
        $this->assertSame(Command::SUCCESS, $commandTester->execute([]));
        $this->assertCount(3, $dbal->getTables());

        $replayCommand = $this->container->get(ReplayCommand::class);
        $commandTester = new CommandTester($replayCommand);
        $exitCode = $commandTester->execute(['--all' => true]);
        $realOutput = $commandTester->getDisplay();
        $expectedOutput = [
            'Rolling back executed migration(s)...',
            'Migration 0_default_create_customers was successfully rolled back.',
            'Migration 0_default_change_articles_add_description was successfully rolled back.',
            'Migration 0_default_create_articles was successfully rolled back.',

            'Executing outstanding migration(s)...',
            'Migration 0_default_create_articles was successfully executed.',
            'Migration 0_default_change_articles_add_description was successfully executed.',
            'Migration 0_default_create_customers was successfully executed.',
        ];

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertCount(3, $dbal->getTables());
        $this->assertThat($expectedOutput, new SeeInOrder($realOutput));
    }
}
