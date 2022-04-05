<?php

declare(strict_types=1);

namespace Wakebit\CycleBridge\Tests\Console\Command\Schema;

use Cycle\Database\DatabaseInterface;
use Cycle\Migrations\Config\MigrationConfig;
use League\Flysystem\Adapter\Local;
use League\Flysystem\FileNotFoundException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Wakebit\CycleBridge\Console\Command\Migrate\MigrateCommand as DatabaseMigrateCommand;
use Wakebit\CycleBridge\Console\Command\Schema\MigrateCommand;
use Wakebit\CycleBridge\Tests\Constraints\SeeInOrder;
use Wakebit\CycleBridge\Tests\TestCase;

final class MigrateCommandTest extends TestCase
{
    private DatabaseInterface $db;
    private \League\Flysystem\Filesystem $files;

    /** @var array<string> */
    private array $migrationFiles;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = $this->container->get(DatabaseInterface::class);

        // Tests root folder
        $adapter = new Local(__DIR__ . '/../../../../');

        $this->files = new \League\Flysystem\Filesystem($adapter);
        $this->migrationFiles = [
            'resources/migrations/20220210.160450_0_0_default_create_articles.php',
            'resources/migrations/20220210.160451_0_0_default_change_articles_add_description.php',
            'resources/migrations/20220210.160452_0_0_default_create_customers.php',
        ];
    }

    protected function tearDown(): void
    {
        $this->rollbackMigrationFiles();
        $this->deleteTestEntity();

        parent::tearDown();
    }

    private function rollbackMigrationFiles(): void
    {
        /** @var array<array<string>> $files */
        $files = $this->files->listContents('resources/migrations/');

        foreach ($files as $file) {
            if (!in_array($file['path'], $this->migrationFiles)) {
                $this->files->delete($file['path']);
            }
        }
    }

    public function testConfiguringMigrator(): void
    {
        $this->assertNoTablesArePresent();

        $commandTester = new CommandTester($this->container->get(MigrateCommand::class));
        $commandTester->execute([]);

        $this->assertMigrationsTableExist();
    }

    public function testOutstandingMigrations(): void
    {
        $commandTester = new CommandTester($this->container->get(MigrateCommand::class));
        $exitCode = $commandTester->execute([]);
        $output = $commandTester->getDisplay();

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('Outstanding migrations found, run `cycle:migrate` first.', $output);
        $this->assertNoChangesInMigrationFiles();
    }

    public function testMigrate(): void
    {
        $this->createTestEntity();
        $this->assertFalse($this->db->table('tags')->exists());
        $this->callDatabaseMigrateCommand();

        $schemaMigrateCommand = $this->container->get(MigrateCommand::class);
        $commandTester = new CommandTester($schemaMigrateCommand);
        $exitCode = $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);
        $realOutput = $commandTester->getDisplay();

        $expectedOutput = [
            'Detecting schema changes:',
            '• default.tags',
            '- create table',
            '- add column id',
        ];

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertThat($expectedOutput, new SeeInOrder($realOutput));
        $this->assertHasChangesInMigrationFiles();
        $this->assertFalse($this->db->table('tags')->exists());
    }

    public function testMigrateWithRun(): void
    {
        $this->createTestEntity();
        $this->assertFalse($this->db->table('tags')->exists());
        $this->callDatabaseMigrateCommand();

        $schemaMigrateCommand = $this->container->get(MigrateCommand::class);
        $commandTester = new CommandTester($schemaMigrateCommand);
        $exitCode = $commandTester->execute(['--run' => true], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);
        $realOutput = $commandTester->getDisplay();

        $expectedOutput = [
            'Detecting schema changes:',
            '• default.tags',
            '- create table',
            '- add column id',
        ];

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertThat($expectedOutput, new SeeInOrder($realOutput));
        $this->assertHasChangesInMigrationFiles();
        $this->assertTrue($this->db->table('tags')->exists());
    }

    private function assertNoTablesArePresent(): void
    {
        $this->assertCount(0, $this->db->getTables());
    }

    private function assertMigrationsTableExist(): void
    {
        $tables = $this->db->getTables();
        $migrationTableName = $this->container->get(MigrationConfig::class)->getTable();

        $this->assertCount(1, $tables);
        $this->assertSame($migrationTableName, $tables[0]->getName());
    }

    private function assertNoChangesInMigrationFiles(): void
    {
        /** @var array<array<string>> $files */
        $files = $this->files->listContents('resources/migrations/');
        $this->assertCount(3, $files);

        foreach ($files as $file) {
            $this->assertContains($file['path'], $this->migrationFiles);
        }
    }

    private function assertHasChangesInMigrationFiles(): void
    {
        /** @var array<array<string>> $files */
        $files = $this->files->listContents('resources/migrations/');

        /** @var array<string> $files */
        $files = array_column($files, 'path');

        $this->assertCount(4, $files);

        foreach ($this->migrationFiles as $migrationFile) {
            $this->assertContains($migrationFile, $files);
        }

        $this->assertStringContainsString('default_create_tags', $files[count($files) - 1]);
    }

    private function createTestEntity(): void
    {
        $content = <<<'PHP'
<?php

declare(strict_types=1);

namespace Wakebit\CycleBridge\TestApp\Entity;

use Cycle\Annotated\Annotation\Column;use Cycle\Annotated\Annotation\Entity;

/**
 * @Entity
 */
class Tag
{
    /**
     * @Column(type="primary")
     */
    public int $id;
}
PHP;

        $this->files->put('App/Entity/Tag.php', $content);
    }

    private function deleteTestEntity(): void
    {
        try {
            $this->files->delete('App/Entity/Tag.php');
        } catch (FileNotFoundException $exception) {
        }
    }

    private function callDatabaseMigrateCommand(): void
    {
        $command = $this->container->get(DatabaseMigrateCommand::class);
        $commandTester = new CommandTester($command);
        $exitCode = $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
    }
}
