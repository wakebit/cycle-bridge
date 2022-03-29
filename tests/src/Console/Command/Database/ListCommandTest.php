<?php

/**
 * Credits: Spiral Cycle Bridge.
 *
 * @see https://github.com/spiral/cycle-bridge
 */

declare(strict_types=1);

namespace Wakebit\CycleBridge\Tests\Console\Command\Database;

use Spiral\Database\Database;
use Spiral\Database\DatabaseInterface;
use Spiral\Database\DatabaseManager;
use Spiral\Database\DatabaseProviderInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Wakebit\CycleBridge\Console\Command\Database\ListCommand;
use Wakebit\CycleBridge\Tests\Constraints\SeeInOrder;
use Wakebit\CycleBridge\Tests\TestCase;

final class ListCommandTest extends TestCase
{
    public function testList(): void
    {
        /** @var Database $db */
        $db = $this->container->get(DatabaseInterface::class);

        $tableA = $db->table('sample')->getSchema();
        $tableA->primary('primary_id');
        $tableA->string('some_string');
        $tableA->index(['some_string'])->setName('custom_index');
        $tableA->integer('b_id');
        $tableA->foreignKey(['b_id'])->references('outer', ['id']);
        $tableA->save();

        $tableB = $db->table('outer')->getSchema();
        $tableB->primary('id');
        $tableB->save();

        $commandTester = new CommandTester($this->container->get(ListCommand::class));
        $exitCode = $commandTester->execute([]);
        $this->assertSame(0, $exitCode);

        $realOutput = $commandTester->getDisplay();
        $expectedOutput = [
            'Name (ID):', 'Database:', 'Driver:', 'Prefix:', 'Status:', 'Tables:', 'Count Records:',
            'default', ':memory:', 'SQLite', '---', 'connected', 'sample', 'outer', '0', '0',
        ];

        $this->assertThat($expectedOutput, new SeeInOrder($realOutput));
    }

    public function testBrokenList(): void
    {
        /** @var DatabaseManager $dm */
        $dm = $this->container->get(DatabaseProviderInterface::class);

        $dm->addDatabase(
            new Database(
                'sqlite',
                '',
                $dm->driver('sqlite')
            )
        );

        $commandTester = new CommandTester($this->container->get(ListCommand::class));
        $exitCode = $commandTester->execute(['database' => 'sqlite']);
        $this->assertSame(0, $exitCode);

        $realOutput = $commandTester->getDisplay();
        $expectedOutput = [
            'Name (ID):', 'Database:', 'Driver:', 'Prefix:', 'Status:', 'Tables:', 'Count Records:',
            'sqlite', ':memory:', 'SQLite', '---', 'connected', 'no tables', 'no records',
        ];

        $this->assertThat($expectedOutput, new SeeInOrder($realOutput));
    }
}
