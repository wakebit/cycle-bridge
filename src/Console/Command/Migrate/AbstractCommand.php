<?php

/**
 * Credits: Spiral Framework, Anton Titov (Wolfy-J).
 *
 * @see https://github.com/spiral/framework
 */

declare(strict_types=1);

namespace Wakebit\CycleBridge\Console\Command\Migrate;

use Cycle\Migrations\Config\MigrationConfig;
use Cycle\Migrations\Migrator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\OutputStyle;

abstract class AbstractCommand extends Command
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    protected InputInterface $input;

    /** @psalm-suppress PropertyNotSetInConstructor */
    protected OutputInterface $output;

    public function __construct(protected Migrator $migrator, protected MigrationConfig $migrationConfig)
    {
        parent::__construct();
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        return parent::run($this->input = $input, $this->output = $output);
    }

    /**
     * Check if current environment is safe to run migration.
     */
    protected function verifyEnvironment(): bool
    {
        if ($this->input->getOption('force') || $this->migrationConfig->isSafe()) {
            // Safe to run
            return true;
        }

        $this->output->writeln('Confirmation is required to run migrations!');

        if (!$this->askConfirmation()) {
            $this->output->writeln('<comment>Cancelling operation...</comment>');

            return false;
        }

        return true;
    }

    protected function askConfirmation(): mixed
    {
        $question = '<question>Would you like to continue?</question> ';

        /**
         * Laravel console test helper uses specific class but real command running uses another one.
         * But this does same below.
         *
         * @see \Illuminate\Testing\PendingCommand::mockConsoleOutput()
         */
        if ($this->output instanceof OutputStyle) {
            return $this->output->confirm($question);
        }

        $questionHelper = new QuestionHelper();

        return $questionHelper->ask(
            $this->input,
            $this->output,
            new ConfirmationQuestion($question, false)
        );
    }
}
