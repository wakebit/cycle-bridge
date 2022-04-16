<?php

/**
 * Credits: Spiral Cycle Bridge.
 *
 * @see https://github.com/spiral/cycle-bridge
 */

declare(strict_types=1);

namespace Wakebit\CycleBridge\Console\Command\Schema;

use Cycle\ORM\SchemaInterface;
use Cycle\Schema\Renderer\OutputSchemaRenderer;
use Cycle\Schema\Renderer\SchemaToArrayConverter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class RenderCommand extends Command
{
    private string $name = 'cycle:schema:render';
    private string $description = 'Render available CycleORM schemas.';

    public function __construct(
        private SchemaInterface $schema,
        private SchemaToArrayConverter $converter,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName($this->name)
            ->setDescription($this->description)
            ->addOption('no-color', 'nc', InputOption::VALUE_NONE, 'Display output without colors.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = $input->getOption('no-color') ?
            OutputSchemaRenderer::FORMAT_PLAIN_TEXT :
            OutputSchemaRenderer::FORMAT_CONSOLE_COLOR;

        $renderer = new OutputSchemaRenderer($format);

        $output->writeln(
            $renderer->render(
                $this->converter->convert($this->schema),
            ),
        );

        return Command::SUCCESS;
    }
}
