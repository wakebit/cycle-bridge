<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Wakebit\CycleBridge\Console\Command\Schema\Generator;

use Cycle\Schema\GeneratorInterface;
use Cycle\Schema\Registry;
use Cycle\Database\Schema\AbstractColumn;
use Cycle\Database\Schema\AbstractForeignKey;
use Cycle\Database\Schema\AbstractIndex;
use Cycle\Database\Schema\AbstractTable;
use Cycle\Database\Schema\ComparatorInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ShowChanges implements GeneratorInterface
{
    private array $changes = [];

    public function __construct(private OutputInterface $output)
    {
    }

    public function run(Registry $registry): Registry
    {
        $this->output->writeln('<info>Detecting schema changes:</info>');
        $this->changes = [];

        foreach ($registry->getIterator() as $e) {
            if ($registry->hasTable($e)) {
                $table = $registry->getTableSchema($e);

                if ($table->getComparator()->hasChanges()) {
                    $this->changes[] = [
                        'database' => $registry->getDatabase($e),
                        'table'    => $registry->getTable($e),
                        'schema'   => $table,
                    ];
                }
            }
        }

        if ($this->changes === []) {
            $this->output->writeln('<fg=yellow>no database changes has been detected</fg=yellow>');

            return $registry;
        }

        foreach ($this->changes as $change) {
            $this->output->writeln(sprintf('• <fg=cyan>%s.%s</fg=cyan>', $change['database'], $change['table']));
            $this->describeChanges($change['schema']);
        }

        return $registry;
    }

    public function hasChanges(): bool
    {
        return $this->changes !== [];
    }

    private function describeChanges(AbstractTable $table): void
    {
        if (!$this->output->isVerbose()) {
            $this->output->writeln(
                sprintf(
                    ': <fg=green>%s</fg=green> change(s) detected',
                    $this->numChanges($table)
                )
            );

            return;
        }
        $this->output->write("\n");

        if (!$table->exists()) {
            $this->output->writeln('    - create table');
        }

        if ($table->getStatus() === AbstractTable::STATUS_DECLARED_DROPPED) {
            $this->output->writeln('    - drop table');

            return;
        }

        $cmp = $table->getComparator();

        $this->describeColumns($cmp);
        $this->describeIndexes($cmp);
        $this->describeFKs($cmp);
    }

    private function describeColumns(ComparatorInterface $cmp): void
    {
        foreach ($cmp->addedColumns() as $column) {
            $this->output->writeln("    - add column <fg=yellow>{$column->getName()}</fg=yellow>");
        }

        foreach ($cmp->droppedColumns() as $column) {
            $this->output->writeln("    - drop column <fg=yellow>{$column->getName()}</fg=yellow>");
        }

        /** @var array<array{AbstractColumn, AbstractColumn}> $alteredColumns */
        $alteredColumns = $cmp->alteredColumns();

        foreach ($alteredColumns as $column) {
            $column = $column[0];
            $this->output->writeln("    - alter column <fg=yellow>{$column->getName()}</fg=yellow>");
        }
    }

    private function describeIndexes(ComparatorInterface $cmp): void
    {
        foreach ($cmp->addedIndexes() as $index) {
            /** @var array<string> $indexColumns */
            $indexColumns = $index->getColumns();
            $implodedIndexColumns = implode(', ', $indexColumns);
            $this->output->writeln("    - add index on <fg=yellow>[{$implodedIndexColumns}]</fg=yellow>");
        }

        foreach ($cmp->droppedIndexes() as $index) {
            /** @var array<string> $indexColumns */
            $indexColumns = $index->getColumns();
            $implodedIndexColumns = implode(', ', $indexColumns);
            $this->output->writeln("    - drop index on <fg=yellow>[{$implodedIndexColumns}]</fg=yellow>");
        }

        /** @var array<array{AbstractIndex, AbstractIndex|null}> $alteredIndexes */
        $alteredIndexes = $cmp->alteredIndexes();

        foreach ($alteredIndexes as $index) {
            $index = $index[0];
            /** @var array<string> $indexColumns */
            $indexColumns = $index->getColumns();
            $implodedIndexColumns = implode(', ', $indexColumns);
            $this->output->writeln("    - alter index on <fg=yellow>[{$implodedIndexColumns}]</fg=yellow>");
        }
    }

    private function describeFKs(ComparatorInterface $cmp): void
    {
        foreach ($cmp->addedForeignKeys() as $fk) {
            /** @var array<string> $fkColumns */
            $fkColumns = $fk->getColumns();
            $implodedFkColumns = implode(', ', $fkColumns);
            $this->output->writeln("    - add foreign key on <fg=yellow>{$implodedFkColumns}</fg=yellow>");
        }

        foreach ($cmp->droppedForeignKeys() as $fk) {
            /** @var array<string> $fkColumns */
            $fkColumns = $fk->getColumns();
            $implodedFkColumns = implode(', ', $fkColumns);
            $this->output->writeln("    - drop foreign key <fg=yellow>{$implodedFkColumns}</fg=yellow>");
        }

        /** @var array<array{AbstractForeignKey, AbstractForeignKey|null}> $alteredForeignKeys */
        $alteredForeignKeys = $cmp->alteredForeignKeys();

        foreach ($alteredForeignKeys as $fk) {
            $fk = $fk[0];
            /** @var array<string> $fkColumns */
            $fkColumns = $fk->getColumns();
            $implodedFkColumns = implode(', ', $fkColumns);
            $this->output->writeln("    - alter foreign key <fg=yellow>{$implodedFkColumns}</fg=yellow>");
        }
    }

    private function numChanges(AbstractTable $table): int
    {
        $cmp = $table->getComparator();

        return count($cmp->addedColumns())
            + count($cmp->droppedColumns())
            + count($cmp->alteredColumns())
            + count($cmp->addedIndexes())
            + count($cmp->droppedIndexes())
            + count($cmp->alteredIndexes())
            + count($cmp->addedForeignKeys())
            + count($cmp->droppedForeignKeys())
            + count($cmp->alteredForeignKeys());
    }
}
