<?php

declare(strict_types=1);

namespace Wakebit\CycleBridge\Tests\Console\Command\Migrate;

use Cycle\Database\DatabaseInterface;
use Spiral\Migrations\Config\MigrationConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Wakebit\CycleBridge\Console\Command\Migrate\MigrateCommand;
use Wakebit\CycleBridge\Console\Command\Migrate\RollbackCommand;
use Wakebit\CycleBridge\Console\Command\Migrate\StatusCommand;
use Wakebit\CycleBridge\Tests\Constraints\SeeInOrder;
use Wakebit\CycleBridge\Tests\TestCase;

final class StatusCommandTest extends TestCase
{
    private DatabaseInterface $db;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = $this->container->get(DatabaseInterface::class);
    }

    public function testNotExecutedState(): void
    {
        $this->assertNoTablesArePresent();

        $commandTester = new CommandTester($this->container->get(StatusCommand::class));
        $exitCode = $commandTester->execute([]);
        $realOutput = $commandTester->getDisplay();

        $expectedOutput = [
            'Migration',                                 'Created at',          'Executed at',
            '0_default_create_articles',                 '2022-02-10 16:04:50', 'not executed yet',
            '0_default_change_articles_add_description', '2022-02-10 16:04:51', 'not executed yet',
            '0_default_create_customers',                '2022-02-10 16:04:52', 'not executed yet',
        ];

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertThat($expectedOutput, new SeeInOrder($realOutput));
        $this->assertOnlyMigrationsTableIsPresent();
    }

    public function testWithoutAnyMigration(): void
    {
        $this->setMigrationConfigValue('directory', __DIR__ . '/../resources/migrations2');

        $commandTester = new CommandTester($this->container->get(StatusCommand::class));
        $exitCode = $commandTester->execute([]);
        $realOutput = $commandTester->getDisplay();

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('No migrations were found.', $realOutput);
    }

    public function testOutputTable(): void
    {
        $formattedCurrentTime = (new \DateTimeImmutable())->format('Y-m-d H:i');
        $this->prepareMigrations();

        $commandTester = new CommandTester($this->container->get(StatusCommand::class));
        $exitCode = $commandTester->execute([]);
        $realOutput = $commandTester->getDisplay();

        $expectedOutput = [
            'Migration',                                 'Created at',          'Executed at',
            '0_default_create_articles',                 '2022-02-10 16:04:50', $formattedCurrentTime,
            '0_default_change_articles_add_description', '2022-02-10 16:04:51', $formattedCurrentTime,
            '0_default_create_customers',                '2022-02-10 16:04:52', 'not executed yet',
        ];

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertThat($expectedOutput, new SeeInOrder($realOutput));
    }

    private function prepareMigrations(): void
    {
        $commandTester = new CommandTester($this->container->get(MigrateCommand::class));
        $exitCode = $commandTester->execute([]);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertAllTablesArePresent();

        $commandTester = new CommandTester($this->container->get(RollbackCommand::class));
        $exitCode = $commandTester->execute([]);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertAllTablesExceptLatestArePresent();
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
