<?php

/**
 * Credits: Spiral Framework, Anton Titov (Wolfy-J).
 *
 * @see https://github.com/spiral/framework
 */

declare(strict_types=1);

namespace Wakebit\CycleBridge\Console\Command\Migrate;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class RollbackCommand extends AbstractCommand
{
    private string $name = 'cycle:migrate:rollback';
    private string $description = 'Rollback the last migration.';

    protected function configure(): void
    {
        $this
            ->setName($this->name)
            ->setDescription($this->description)
            ->addOption('force', 's', InputOption::VALUE_NONE, 'Force the operation to run when in production.')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Rollback all executed migrations.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->verifyEnvironment()) {
            return self::FAILURE;
        }

        $this->migrator->configure();

        $found = false;
        $count = !$this->input->getOption('all') ? 1 : PHP_INT_MAX;

        while ($count > 0 && ($migration = $this->migrator->rollback())) {
            $found = true;
            --$count;

            /** @psalm-suppress InternalMethod */
            $message = sprintf(
                '<info>Migration <comment>%s</comment> was successfully rolled back.</info>',
                $migration->getState()->getName()
            );

            $this->output->writeln($message);
        }

        if (!$found) {
            $this->output->writeln('<fg=red>No executed migrations were found.</fg=red>');
        }

        return Command::SUCCESS;
    }
}
