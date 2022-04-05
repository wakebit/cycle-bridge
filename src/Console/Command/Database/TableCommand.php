<?php

/**
 * Credits: Spiral Framework, Anton Titov (Wolfy-J).
 *
 * @see https://github.com/spiral/framework
 */

declare(strict_types=1);

namespace Wakebit\CycleBridge\Console\Command\Database;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseProviderInterface;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Exception\DBALException;
use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Query\QueryParameters;
use Cycle\Database\Schema\AbstractColumn;
use Cycle\Database\Schema\AbstractForeignKey;
use Cycle\Database\Schema\AbstractIndex;
use Cycle\Database\Schema\AbstractTable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class TableCommand extends Command
{
    private const SKIP = '<comment>---</comment>';

    private string $name = 'cycle:db:table';
    private string $description = 'Describe table schema of specific database';

    private InputInterface $input;
    private OutputInterface $output;
    private string $tableName;

    public function __construct(private DatabaseProviderInterface $dbal)
    {
        parent::__construct();
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        return parent::run($this->input = $input, $this->output = $output);
    }

    protected function configure(): void
    {
        $this
            ->setName($this->name)
            ->setDescription($this->description)
            ->addArgument('table', InputArgument::REQUIRED, 'Table name')
            ->addOption('database', 'db', InputOption::VALUE_OPTIONAL, 'Source database', 'default');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string|null $databaseOptionValue */
        $databaseOptionValue = $input->getOption('database');
        $database = $this->dbal->database($databaseOptionValue);

        /** @var non-empty-string */
        $this->tableName = $this->input->getArgument('table');

        /** @var \Cycle\Database\Table $table */
        $table = $database->table($this->tableName);
        $schema = $table->getSchema();

        if (!$schema->exists()) {
            throw new DBALException(
                "Table {$database->getName()}.$this->tableName does not exists."
            );
        }

        $message = sprintf(
            "\n<fg=cyan>Columns of </fg=cyan><comment>%s.%s</comment>:\n",
            $database->getName(),
            $this->tableName
        );

        $this->output->writeln($message);
        $this->describeColumns($schema);

        if (!empty($indexes = $schema->getIndexes())) {
            $this->describeIndexes($database, $indexes);
        }

        if (!empty($foreignKeys = $schema->getForeignKeys())) {
            $this->describeForeignKeys($database, $foreignKeys);
        }

        $this->output->write("\n");

        return Command::SUCCESS;
    }

    protected function describeColumns(AbstractTable $schema): void
    {
        $columnsTable = (new Table($this->output))
            ->setHeaders(
                [
                    'Column:',
                    'Database Type:',
                    'Abstract Type:',
                    'PHP Type:',
                    'Default Value:',
                ]
            );

        foreach ($schema->getColumns() as $column) {
            $name = $column->getName();

            if (in_array($column->getName(), $schema->getPrimaryKeys(), true)) {
                $name = "<fg=magenta>{$name}</fg=magenta>";
            }

            $defaultValue = $this->describeDefaultValue($column, $schema->getDriver());

            $columnsTable->addRow(
                [
                    $name,
                    $this->describeType($column),
                    $this->describeAbstractType($column),
                    $column->getType(),
                    $defaultValue ?? self::SKIP,
                ]
            );
        }

        $columnsTable->render();
    }

    /**
     * @param array<AbstractIndex> $indexes
     */
    protected function describeIndexes(DatabaseInterface $database, array $indexes): void
    {
        $message = sprintf(
            "\n<fg=cyan>Indexes of </fg=cyan><comment>%s.%s</comment>:\n",
            $database->getName(),
            $this->tableName
        );

        $this->output->writeln($message);

        $indexesTable = (new Table($this->output))
            ->setHeaders(['Name:', 'Type:', 'Columns:']);

        foreach ($indexes as $index) {
            /** @var array<string> $columns */
            $columns = $index->getColumns();

            $indexesTable->addRow(
                [
                    $index->getName(),
                    $index->isUnique() ? 'UNIQUE INDEX' : 'INDEX',
                    implode(', ', $columns),
                ]
            );
        }

        $indexesTable->render();
    }

    /**
     * @param array<AbstractForeignKey> $foreignKeys
     */
    protected function describeForeignKeys(DatabaseInterface $database, array $foreignKeys): void
    {
        $message = sprintf(
            "\n<fg=cyan>Foreign Keys of </fg=cyan><comment>%s.%s</comment>:\n",
            $database->getName(),
            $this->tableName
        );

        $this->output->writeln($message);

        $foreignTable = (new Table($this->output))
            ->setHeaders(
                [
                    'Name:',
                    'Column:',
                    'Foreign Table:',
                    'Foreign Column:',
                    'On Delete:',
                    'On Update:',
                ]
            );

        foreach ($foreignKeys as $reference) {
            /** @var array<string> $columns */
            $columns = $reference->getColumns();

            /** @var array<string> $foreignColumns */
            $foreignColumns = $reference->getForeignKeys();

            $foreignTable->addRow(
                [
                    $reference->getName(),
                    implode(', ', $columns),
                    $reference->getForeignTable(),
                    implode(', ', $foreignColumns),
                    $reference->getDeleteRule(),
                    $reference->getUpdateRule(),
                ]
            );
        }

        $foreignTable->render();
    }

    protected function describeDefaultValue(AbstractColumn $column, DriverInterface $driver): float|\DateTimeInterface|bool|int|string|FragmentInterface|null
    {
        /** @var FragmentInterface|\DateTimeInterface|scalar|null $defaultValue */
        $defaultValue = $column->getDefaultValue();

        if ($defaultValue instanceof FragmentInterface) {
            $value = $driver->getQueryCompiler()->compile(new QueryParameters(), '', $defaultValue);

            return "<info>{$value}</info>";
        }

        if ($defaultValue instanceof \DateTimeInterface) {
            $defaultValue = $defaultValue->format('c');
        }

        return $defaultValue;
    }

    private function describeType(AbstractColumn $column): string
    {
        $type = $column->getType();

        $abstractType = $column->getAbstractType();

        if ($column->getSize()) {
            $type .= " ({$column->getSize()})";
        }

        if ($abstractType === 'decimal') {
            $type .= " ({$column->getPrecision()}, {$column->getScale()})";
        }

        return $type;
    }

    private function describeAbstractType(AbstractColumn $column): string
    {
        $abstractType = $column->getAbstractType();

        if (in_array($abstractType, ['primary', 'bigPrimary'])) {
            $abstractType = "<fg=magenta>{$abstractType}</fg=magenta>";
        }

        return $abstractType;
    }
}
