<?php

/**
 * Credits: Spiral Framework, Anton Titov (Wolfy-J).
 *
 * @see https://github.com/spiral/framework
 */

declare(strict_types=1);

namespace Wakebit\CycleBridge\Console\Command\Migrate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class MigrateCommand extends AbstractCommand
{
    /** @var string */
    private $name = 'cycle:migrate';

    /** @var string */
    private $description = 'Run all outstanding migrations.';

    protected function configure(): void
    {
        $this
            ->setName($this->name)
            ->setDescription($this->description)
            ->addOption('force', 's', InputOption::VALUE_NONE, 'Force the operation to run when in production.')
            ->addOption('one', 'o', InputOption::VALUE_NONE, 'Execute only one (first) migration.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->verifyEnvironment()) {
            return 1;
        }

        $this->migrator->configure();

        $found = false;
        $count = $this->input->getOption('one') ? 1 : PHP_INT_MAX;

        while ($count > 0 && ($migration = $this->migrator->run())) {
            $found = true;
            --$count;

            /** @psalm-suppress InternalMethod */
            $message = sprintf(
                '<info>Migration <comment>%s</comment> was successfully executed.</info>',
                $migration->getState()->getName()
            );

            $this->output->writeln($message);
        }

        if (!$found) {
            $this->output->writeln('<fg=red>No outstanding migrations were found.</fg=red>');
        }

        return 0;
    }
}
