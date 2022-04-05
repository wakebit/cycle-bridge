<?php

declare(strict_types=1);

namespace Wakebit\CycleBridge\Console\Command\Schema;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wakebit\CycleBridge\Contracts\Schema\CacheManagerInterface;
use Wakebit\CycleBridge\Contracts\Schema\CompilerInterface;
use Wakebit\CycleBridge\Contracts\Schema\GeneratorQueueInterface;
use Wakebit\CycleBridge\Schema\Config\SchemaConfig;

final class CacheCommand extends Command
{
    private string $name = 'cycle:schema:cache';
    private string $description = 'Compile and cache ORM schema from database and annotated classes.';

    public function __construct(
        private GeneratorQueueInterface $generatorQueue,
        private CacheManagerInterface $cacheManager,
        private CompilerInterface $schemaCompiler,
        private SchemaConfig $schemaConfig
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
        /** @var array|null $manuallyDefinedSchema */
        $manuallyDefinedSchema = $this->schemaConfig->getManuallyDefinedSchema();

        $schema = $manuallyDefinedSchema !== null
            ? $manuallyDefinedSchema
            : $this->schemaCompiler->compile($this->generatorQueue);

        $this->cacheManager->write($schema);

        $output->writeln('<info>ORM schema cached successfully!</info>');

        return Command::SUCCESS;
    }
}
