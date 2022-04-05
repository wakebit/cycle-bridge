<?php

declare(strict_types=1);

namespace Wakebit\CycleBridge\Tests\Console\Command\Schema;

use League\Flysystem\Adapter\Local;
use Spiral\Database\DatabaseInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Wakebit\CycleBridge\Console\Command\Migrate\MigrateCommand as DatabaseMigrateCommand;
use Wakebit\CycleBridge\Console\Command\Schema\SyncCommand;
use Wakebit\CycleBridge\Tests\Constraints\SeeInOrder;
use Wakebit\CycleBridge\Tests\TestCase;

final class SyncCommandTest extends TestCase
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
        $this->deleteTestEntity();

        parent::tearDown();
    }

    public function testSync(): void
    {
        $this->createTestEntity();
        $this->assertFalse($this->db->table('tags')->exists());
        $this->callDatabaseMigrateCommand();

        $schemaMigrateCommand = $this->container->get(SyncCommand::class);
        $commandTester = new CommandTester($schemaMigrateCommand);
        $exitCode = $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);
        $realOutput = $commandTester->getDisplay();

        $expectedOutput = [
            'Detecting schema changes:',
            '• default.tags',
            '- create table',
            '- add column id',
            'ORM Schema has been synchronized.',
        ];

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertThat($expectedOutput, new SeeInOrder($realOutput));
        $this->assertNoChangesInMigrationFiles();
        $this->assertTrue($this->db->table('tags')->exists());
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
        $this->files->delete('App/Entity/Tag.php');
    }

    private function callDatabaseMigrateCommand(): void
    {
        $command = $this->container->get(DatabaseMigrateCommand::class);
        $commandTester = new CommandTester($command);
        $exitCode = $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
    }
}
