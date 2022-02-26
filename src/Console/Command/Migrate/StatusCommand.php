<?php

/**
 * Credits: Spiral Framework, Anton Titov (Wolfy-J).
 *
 * @see https://github.com/spiral/framework
 */

declare(strict_types=1);

namespace Wakebit\CycleBridge\Console\Command\Migrate;

use Spiral\Migrations\State;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @psalm-suppress DeprecatedClass Compatibility with old version spiral/migrations
 */
final class StatusCommand extends AbstractCommand
{
    private const DATE_FORMAT = 'Y-m-d H:i:s';

    /** @var string */
    private $name = 'cycle:migrate:status';

    /** @var string */
    private $description = 'Get a list of available migrations.';

    protected function configure(): void
    {
        $this
            ->setName($this->name)
            ->setDescription($this->description);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->migrator->configure();
        $migrations = $this->migrator->getMigrations();

        if (empty($migrations)) {
            $this->output->writeln('<comment>No migrations were found.</comment>');

            return 1;
        }

        $table = (new Table($output))->setHeaders(['Migration', 'Created at', 'Executed at']);

        /** @psalm-suppress InternalMethod */
        foreach ($migrations as $migration) {
            $state = $migration->getState();
            $timeCreated = $state->getTimeCreated();
            $timeExecuted = $state->getTimeExecuted();
            $timeExecutedOutput = $timeExecuted instanceof \DateTimeInterface
                ? $timeExecuted->format(self::DATE_FORMAT)
                : null;

            $table->addRow(
                [
                    $state->getName(),
                    $timeCreated->format(self::DATE_FORMAT),
                    $state->getStatus() === State::STATUS_PENDING
                        ? '<fg=red>not executed yet</fg=red>'
                        : "<info>$timeExecutedOutput</info>",
                ]
            );
        }

        $table->render();

        return 0;
    }
}
