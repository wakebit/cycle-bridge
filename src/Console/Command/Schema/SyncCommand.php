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
    /** @var string */
    private $name = 'cycle:schema:sync';

    /** @var string */
    private $description = 'Sync ORM schema with database without intermediate migration (risk operation).';

    /** @var GeneratorQueueInterface */
    private $generatorQueue;

    /** @var CompilerInterface */
    private $schemaCompiler;

    /** @var SyncTables */
    private $syncTables;

    public function __construct(
        GeneratorQueueInterface $generatorQueue,
        CompilerInterface $schemaCompiler,
        SyncTables $syncTables
    ) {
        parent::__construct();

        $this->generatorQueue = $generatorQueue;
        $this->schemaCompiler = $schemaCompiler;
        $this->syncTables = $syncTables;
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
