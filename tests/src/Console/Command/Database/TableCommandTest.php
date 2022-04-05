<?php

/**
 * Credits: Spiral Cycle Bridge.
 *
 * @see https://github.com/spiral/cycle-bridge
 */

declare(strict_types=1);

namespace Wakebit\CycleBridge\Tests\Console\Command\Database;

use Cycle\Database\Database;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\Exception\DBALException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Wakebit\CycleBridge\Console\Command\Database\TableCommand;
use Wakebit\CycleBridge\Tests\Constraints\SeeInOrder;
use Wakebit\CycleBridge\Tests\TestCase;

final class TableCommandTest extends TestCase
{
    public function testDescribeWrongDB(): void
    {
        $this->expectException(DBALException::class);

        $commandTester = new CommandTester($this->container->get(TableCommand::class));
        $commandTester->execute([
            '--database' => 'missing',
            'table'      => 'missing',
        ]);
    }

    public function testDescribeWrongTable(): void
    {
        $this->expectException(DBALException::class);

        $commandTester = new CommandTester($this->container->get(TableCommand::class));
        $commandTester->execute([
            '--database' => 'runtime',
            'table'      => 'missing',
        ]);
    }

    public function testDescribeExisted(): void
    {
        /** @var Database $db */
        $db = $this->container->get(DatabaseInterface::class);

        $table = $db->table('sample1')->getSchema();
        $table->primary('primary_id');
        $table->string('some_string');
        $table->index(['some_string'])->setName('custom_index_1');
        $table->save();

        $table = $db->table('sample')->getSchema();
        $table->primary('primary_id');
        $table->integer('primary1_id');
        $table->foreignKey(['primary1_id'])->references('sample1', ['primary_id']);
        $table->integer('some_int');
        $table->index(['some_int'])->setName('custom_index');
        $table->save();

        $commandTester = new CommandTester($this->container->get(TableCommand::class));
        $exitCode = $commandTester->execute(['--database' => 'default', 'table' => 'sample']);
        $this->assertSame(Command::SUCCESS, $exitCode);

        $realOutput = $commandTester->getDisplay();
        $expectedOutput = [
            'Columns of default.sample',
            'Column:', 'Database Type:', 'Abstract Type:', 'PHP Type:', 'Default Value:',

            'primary_id', 'int', /* 'integer', */ 'int', '---', // integer was before, primary is now
            'primary1_id', 'int', 'integer', 'int', '---',
            'some_int', 'int', 'integer', 'int', '---',

            'Indexes of default.sample:',
            'Name:', 'Type:', 'Columns:',

            'custom_index', 'INDEX', 'some_int',
            'sample_index_primary1_id_', 'INDEX', 'primary1_id',

            'Foreign Keys of default.sample:',
            'Name:', 'Column:', 'Foreign Table:', 'Foreign Column:', 'On Delete:', 'On Update:',
            'sample_primary1_id_fk', 'primary1_id', 'sample1', 'primary_id', 'NO ACTION', 'NO ACTION',
        ];

        $this->assertThat($expectedOutput, new SeeInOrder($realOutput));
    }
}
