<?php

/**
 * Credits: Spiral Framework, Anton Titov (Wolfy-J).
 *
 * @see https://github.com/spiral/framework
 */

declare(strict_types=1);

namespace Wakebit\CycleBridge\Console\Command\Database;

use Spiral\Database\Config\DatabaseConfig;
use Spiral\Database\DatabaseInterface;
use Spiral\Database\DatabaseProviderInterface;
use Spiral\Database\Driver\Driver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ListCommand extends Command
{
    /** @var string */
    private $name = 'cycle:db:list';

    /** @var string */
    private $description = 'Get list of available databases, their tables and records count';

    /** @var DatabaseConfig */
    private $databaseConfig;

    /** @var DatabaseProviderInterface */
    private $dbal;

    public function __construct(DatabaseConfig $databaseConfig, DatabaseProviderInterface $dbal)
    {
        parent::__construct();

        $this->databaseConfig = $databaseConfig;
        $this->dbal = $dbal;
    }

    protected function configure(): void
    {
        $this
            ->setName($this->name)
            ->setDescription($this->description)
            ->addArgument('database', InputArgument::OPTIONAL, 'Database name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string|null $databaseArgumentValue */
        $databaseArgumentValue = $input->getArgument('database');

        /** @var array<string> $databases */
        $databases = $databaseArgumentValue
            ? [$databaseArgumentValue]
            : array_keys($this->databaseConfig->getDatabases());

        if (empty($databases)) {
            $output->writeln('<fg=red>No databases found.</fg=red>');

            return 0;
        }

        $grid = (new Table($output))
            ->setHeaders(
                [
                    'Name (ID):',
                    'Database:',
                    'Driver:',
                    'Prefix:',
                    'Status:',
                    'Tables:',
                    'Count Records:',
                ]
            );

        foreach ($databases as $database) {
            $database = $this->dbal->database($database);

            /** @var Driver $driver */
            $driver = $database->getDriver();

            $header = [
                $database->getName(),
                $driver->getSource(),
                $driver->getType(),
                $database->getPrefix() ?: '<comment>---</comment>',
            ];

            try {
                $driver->connect();
            } catch (\Exception $exception) {
                $this->renderException($grid, $header, $exception);

                if ($database->getName() != end($databases)) {
                    $grid->addRow(new TableSeparator());
                }

                continue;
            }

            $header[] = '<info>connected</info>';
            $this->renderTables($grid, $header, $database);
            if ($database->getName() != end($databases)) {
                $grid->addRow(new TableSeparator());
            }
        }

        $grid->render();

        return 0;
    }

    private function renderException(Table $grid, array $header, \Throwable $exception): void
    {
        $grid->addRow(
            array_merge(
                $header,
                [
                    "<fg=red>{$exception->getMessage()}</fg=red>",
                    '<comment>---</comment>',
                    '<comment>---</comment>',
                ]
            )
        );
    }

    private function renderTables(Table $grid, array $header, DatabaseInterface $database): void
    {
        foreach ($database->getTables() as $table) {
            $grid->addRow(
                array_merge(
                    $header,
                    [$table->getName(), $database->select()->from($table->getName())->count()]
                )
            );
            $header = ['', '', '', '', ''];
        }

        $header[1] && $grid->addRow(array_merge($header, ['no tables', 'no records']));
    }
}
