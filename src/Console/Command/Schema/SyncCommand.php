<?php

declare(strict_types=1);

namespace Wakebit\CycleBridge\Console\Command\Schema;

use Cycle\Schema\Generator\SyncTables;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wakebit\CycleBridge\Console\Command\Schema\Generator\ShowChanges;
use Wakebit\CycleBridge\Contracts\Schema\CompilerInterface;
use Wakebit\CycleBridge\Contracts\Schema\GeneratorQueueInterface;

final class SyncCommand extends Command
{
    private string $name = 'cycle:schema:sync';
    private string $description = 'Sync ORM schema with database without intermediate migration (risk operation).';

    public function __construct(
        private GeneratorQueueInterface $generatorQueue,
        private CompilerInterface $schemaCompiler,
        private SyncTables $syncTables
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName($this->name)
            ->setDescription($this->description);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $changesGenerator = new ShowChanges($output);

        $generatorQueue = $this->generatorQueue
            ->addGenerator(GeneratorQueueInterface::GROUP_POSTPROCESS, $changesGenerator)
            ->addGenerator(GeneratorQueueInterface::GROUP_POSTPROCESS, $this->syncTables);

        $this->schemaCompiler->compile($generatorQueue);

        if ($changesGenerator->hasChanges()) {
            $output->writeln("\n<info>ORM Schema has been synchronized.</info>");
        }

        return 0;
    }
}
