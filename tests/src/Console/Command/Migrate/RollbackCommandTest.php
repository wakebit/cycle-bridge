<?php

declare(strict_types=1);

namespace Wakebit\CycleBridge\Tests\Console\Command\Migrate;

use Cycle\Database\DatabaseInterface;
use Spiral\Migrations\Config\MigrationConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Wakebit\CycleBridge\Console\Command\Migrate\InitCommand;
use Wakebit\CycleBridge\Console\Command\Migrate\MigrateCommand;
use Wakebit\CycleBridge\Console\Command\Migrate\RollbackCommand;
use Wakebit\CycleBridge\Tests\Constraints\SeeInOrder;
use Wakebit\CycleBridge\Tests\TestCase;

final class RollbackCommandTest extends TestCase
{
    private DatabaseInterface $db;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = $this->container->get(DatabaseInterface::class);
    }

    public function testCancellationWhenEnvironmentIsNotSafe(): void
    {
        $this->setMigrationConfigValue('safe', false);
        $this->callMigrateCommand();

        $commandTester = new CommandTester($this->container->get(RollbackCommand::class));
        $commandTester->setInputs(['no']);
        $exitCode = $commandTester->execute([]);
        $realOutput = $commandTester->getDisplay();

        $expectedOutput = [
            'Confirmation is required to run migrations!',
            'Would you like to continue?',
            'Cancelling operation...',
        ];

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertThat($expectedOutput, new SeeInOrder($realOutput));
        $this->assertStringNotContainsString('rolled back', $realOutput);
        $this->assertAllTablesArePresent();
    }

    public function testConfirmationWhenEnvironmentIsNotSafe(): void
    {
        $this->setMigrationConfigValue('safe', false);
        $this->callMigrateCommand();

        $commandTester = new CommandTester($this->container->get(RollbackCommand::class));
        $commandTester->setInputs(['yes']);
        $exitCode = $commandTester->execute([]);
        $realOutput = $commandTester->getDisplay();

        $expectedOutput = [
            'Confirmation is required to run migrations!',
            'Would you like to continue?',
            'Migration 0_default_create_customers was successfully rolled back.',
        ];

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertThat($expectedOutput, new SeeInOrder($realOutput));
        $this->assertStringNotContainsString('Cancelling operation...', $realOutput);
        $this->assertAllTablesExceptLatestArePresent();
    }

    public function testForceRunningWhenEnvironmentIsNotSafe(): void
    {
        $this->setMigrationConfigValue('safe', false);
        $this->callMigrateCommand();

        $commandTester = new CommandTester($this->container->get(RollbackCommand::class));
        $exitCode = $commandTester->execute(['--force' => true]);
        $realOutput = $commandTester->getDisplay();

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Migration 0_default_create_customers was successfully rolled back.', $realOutput);
        $this->assertAllTablesExceptLatestArePresent();
    }

    public function testRollingBackAllMigrations(): void
    {
        $this->callMigrateCommand();

        $commandTester = new CommandTester($this->container->get(RollbackCommand::class));
        $exitCode = $commandTester->execute(['--all' => true]);
        $realOutput = $commandTester->getDisplay();

        $expectedOutput = [
            'Migration 0_default_create_customers was successfully rolled back.',
            'Migration 0_default_change_articles_add_description was successfully rolled back.',
            'Migration 0_default_create_articles was successfully rolled back.',
        ];

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertThat($expectedOutput, new SeeInOrder($realOutput));
        $this->assertOnlyMigrationsTableIsPresent();
    }

    public function testRunningWithoutExecutedMigrations(): void
    {
        $this->assertNoTablesArePresent();

        $commandTester = new CommandTester($this->container->get(RollbackCommand::class));
        $exitCode = $commandTester->execute([]);
        $realOutput = $commandTester->getDisplay();

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('No executed migrations were found.', $realOutput);
        $this->assertOnlyMigrationsTableIsPresent();
    }

    public function testWithInitiatedMigrator(): void
    {
        $commandTester = new CommandTester($this->container->get(InitCommand::class));
        $exitCode = $commandTester->execute([]);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertOnlyMigrationsTableIsPresent();

        $commandTester = new CommandTester($this->container->get(RollbackCommand::class));
        $exitCode = $commandTester->execute([]);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertOnlyMigrationsTableIsPresent();
    }

    public function testWithNonConfiguredMigrator(): void
    {
        $this->assertNoTablesArePresent();

        $commandTester = new CommandTester($this->container->get(RollbackCommand::class));
        $exitCode = $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertOnlyMigrationsTableIsPresent();
    }

    private function callMigrateCommand(): void
    {
        $commandTester = new CommandTester($this->container->get(MigrateCommand::class));
        $exitCode = $commandTester->execute(['--force' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertAllTablesArePresent();
    }

    private function assertNoTablesArePresent(): void
    {
        $this->assertCount(0, $this->db->getTables());
    }

    private function assertOnlyMigrationsTableIsPresent(): void
    {
        $tables = $this->db->getTables();
        $migrationTableName = $this->container->get(MigrationConfig::class)->getTable();

        $this->assertCount(1, $tables);
        $this->assertSame($migrationTableName, $tables[0]->getName());
    }

    private function assertAllTablesArePresent(): void
    {
        $tables = $this->db->getTables();
        $migrationTableName = $this->container->get(MigrationConfig::class)->getTable();

        $this->assertCount(3, $tables);
        $this->assertSame($migrationTableName, $tables[0]->getName());
        $this->assertSame('articles', $tables[1]->getName());
        $this->assertSame('customers', $tables[2]->getName());
    }

    private function assertAllTablesExceptLatestArePresent(): void
    {
        $tables = $this->db->getTables();
        $migrationTableName = $this->container->get(MigrationConfig::class)->getTable();

        $this->assertCount(2, $tables);
        $this->assertSame($migrationTableName, $tables[0]->getName());
        $this->assertSame('articles', $tables[1]->getName());
    }
}
