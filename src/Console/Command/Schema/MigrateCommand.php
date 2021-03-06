<?php

/**
 * Credits: Spiral Framework, Anton Titov (Wolfy-J).
 *
 * @see https://github.com/spiral/framework
 */

declare(strict_types=1);

namespace Wakebit\CycleBridge\Console\Command\Schema;

use Cycle\Migrations\Migrator;
use Cycle\Migrations\State;
use Cycle\Schema\Generator\Migrations\GenerateMigrations;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wakebit\CycleBridge\Console\Command\Migrate\MigrateCommand as DatabaseMigrateCommand;
use Wakebit\CycleBridge\Console\Command\Schema\Generator\ShowChanges;
use Wakebit\CycleBridge\Contracts\Schema\CompilerInterface;
use Wakebit\CycleBridge\Contracts\Schema\GeneratorQueueInterface;

final class MigrateCommand extends Command
{
    private string $name = 'cycle:schema:migrate';
    private string $description = 'Generate ORM schema migrations.';

    public function __construct(
        private ContainerInterface $container,
        private GenerateMigrations $generateMigrations,
        private GeneratorQueueInterface $generatorQueue,
        private Migrator $migrator,
        private CompilerInterface $schemaCompiler
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName($this->name)
            ->setDescription($this->description)
            ->addOption('run', 'r', InputOption::VALUE_NONE, 'Automatically run generated migration.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->migrator->configure();

        foreach ($this->migrator->getMigrations() as $migration) {
            /**
             * @psalm-suppress InternalMethod
             * @psalm-suppress DeprecatedClass Compatibility with old version spiral/migrations
             */
            if ($migration->getState()->getStatus() !== State::STATUS_EXECUTED) {
                $output->writeln('<fg=red>Outstanding migrations found, run `cycle:migrate` first.</fg=red>');

                return self::FAILURE;
            }
        }

        $changes = new ShowChanges($output);

        $generatorQueue = $this->generatorQueue
            ->addGenerator(GeneratorQueueInterface::GROUP_POSTPROCESS, $changes);

        $this->schemaCompiler->compile($generatorQueue);

        if ($changes->hasChanges()) {
            $generatorQueue = $generatorQueue
                ->withoutGenerators()
                ->addGenerator(GeneratorQueueInterface::GROUP_POSTPROCESS, $this->generateMigrations);

            $this->schemaCompiler->compile($generatorQueue);

            if ($input->getOption('run')) {
                $command = $this->container->get(DatabaseMigrateCommand::class);
                $command->run(new ArrayInput([]), $output);
            }
        }

        return Command::SUCCESS;
    }
}
