<?php

declare(strict_types=1);

namespace Wakebit\CycleBridge\Console\Command\Schema;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wakebit\CycleBridge\Contracts\Schema\CacheManagerInterface;

final class ClearCommand extends Command
{
    /** @var string */
    private $name = 'cycle:schema:clear';

    /** @var string */
    private $description = 'Clear ORM schema cache.';

    /** @var CacheManagerInterface */
    private $cacheManager;

    public function __construct(CacheManagerInterface $cacheManager)
    {
        parent::__construct();

        $this->cacheManager = $cacheManager;
    }

    protected function configure(): void
    {
        $this
            ->setName($this->name)
            ->setDescription($this->description);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->cacheManager->clear();
        $output->writeln('<info>ORM schema cache cleared!</info>');

        return 0;
    }
}
