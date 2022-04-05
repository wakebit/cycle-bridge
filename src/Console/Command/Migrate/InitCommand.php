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
use Symfony\Component\Console\Output\OutputInterface;

final class InitCommand extends AbstractCommand
{
    private string $name = 'cycle:migrate:init';
    private string $description = 'Init migrations component (create migrations table).';

    protected function configure(): void
    {
        $this
            ->setName($this->name)
            ->setDescription($this->description);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->migrator->configure();
        $this->output->writeln('<info>Migrations table were successfully created.</info>');

        return Command::SUCCESS;
    }
}
