<?php

/**
 * Credits: Spiral Framework, Anton Titov (Wolfy-J).
 *
 * @see https://github.com/spiral/framework
 */

declare(strict_types=1);

namespace Wakebit\CycleBridge\Console\Command\Database;

use Spiral\Database\DatabaseInterface;
use Spiral\Database\DatabaseProviderInterface;
use Spiral\Database\Driver\DriverInterface;
use Spiral\Database\Exception\DBALException;
use Spiral\Database\Injection\FragmentInterface;
use Spiral\Database\Query\QueryParameters;
use Spiral\Database\Schema\AbstractColumn;
use Spiral\Database\Schema\AbstractForeignKey;
use Spiral\Database\Schema\AbstractIndex;
use Spiral\Database\Schema\AbstractTable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class TableCommand extends Command
{
    private const SKIP = '<comment>---</comment>';

    /** @var string */
    private $name = 'cycle:db:table';

    /** @var string */
    private $description = 'Describe table schema of specific database';

    /** @var DatabaseProviderInterface */
    private $dbal;

    /**
     * @var InputInterface
     *
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private $input;

    /**
     * @var OutputInterface
     *
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private $output;

    /**
     * @var string
     *
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private $tableName;

    public function __construct(DatabaseProviderInterface $dbal)
    {
        parent::__construct();

        $this->dbal = $dbal;
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

        /** @var string */
        $this->tableName = $this->input->getArgument('table');

        /** @var \Spiral\Database\Table $table */
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

        return 0;
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

    /**
     * @return scalar|null
     */
    protected function describeDefaultValue(AbstractColumn $column, DriverInterface $driver)
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
