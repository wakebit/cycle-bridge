<?php

declare(strict_types=1);

namespace Wakebit\CycleBridge\Tests\Console\Command\Schema;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Select\Repository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Wakebit\CycleBridge\Console\Command\Schema\RenderCommand;
use Wakebit\CycleBridge\TestApp\Entity\Article;
use Wakebit\CycleBridge\TestApp\Entity\Customer;
use Wakebit\CycleBridge\Tests\Constraints\SeeInOrder;
use Wakebit\CycleBridge\Tests\TestCase;

final class RenderCommandTest extends TestCase
{
    public function testRender(): void
    {
        $commandTester = new CommandTester($this->container->get(RenderCommand::class));
        $exitCode = $commandTester->execute(['-nc' => true], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);
        $realOutput = $commandTester->getDisplay();
        $expectedOutput = [
            '[customer] :: default.customers',
            'Entity:', Customer::class,
            'Mapper:', Mapper::class,
            'Repository:', Repository::class,
            'Primary key:', 'id',
            'Fields', '(property -> db.field -> typecast)', 'id -> id -> int', 'name -> name',
            'Relations:', 'not defined',

            '[article] :: default.articles',
            'Entity:', Article::class,
            'Mapper:', Mapper::class,
            'Repository:', Repository::class,
            'Primary key:', 'id',
            'Fields', '(property -> db.field -> typecast)', 'id -> id -> int', 'title -> title', 'description -> description',
            'Relations:', 'not defined',
        ];

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertThat($expectedOutput, new SeeInOrder($realOutput));
    }
}
