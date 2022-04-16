<?php

/**
 * Credits: Spiral Cycle Bridge.
 *
 * @see https://github.com/spiral/cycle-bridge
 */

declare(strict_types=1);

namespace Wakebit\CycleBridge\Console\Command\Migrate;

use Cycle\Migrations\Config\MigrationConfig;
use Cycle\Migrations\Migrator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ReplayCommand extends AbstractCommand
{
    private string $name = 'cycle:migrate:replay';
    private string $description = 'Replay (down, up) one or multiple migrations.';

    public function __construct(
        protected Migrator $migrator,
        protected MigrationConfig $migrationConfig,
        private RollbackCommand $rollbackCommand,
        private MigrateCommand $migrateCommand,
    ) {
        parent::__construct($migrator, $migrationConfig);
    }

    protected function configure(): void
    {
        $this
            ->setName($this->name)
            ->setDescription($this->description)
            ->addOption('force', 's', InputOption::VALUE_NONE, 'Force the operation to run when in production.')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Replay all migrations.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->verifyEnvironment()) {
            // Making sure we can safely migrate in this environment
            return self::FAILURE;
        }

        $rollback = ['--force' => true];
        $migrate = ['--force' => true];

        if ($input->getOption('all')) {
            $rollback['--all'] = true;
        } else {
            $migrate['--one'] = true;
        }

        $output->writeln('Rolling back executed migration(s)...');
        $this->rollbackCommand->run(new ArrayInput($rollback), $output);

        $output->writeln('');

        $output->writeln('Executing outstanding migration(s)...');
        $this->migrateCommand->run(new ArrayInput($migrate), $output);

        return Command::SUCCESS;
    }
}
