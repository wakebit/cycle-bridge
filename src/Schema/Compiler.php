<?php

declare(strict_types=1);

namespace Wakebit\CycleBridge\Schema;

use Cycle\Schema\Registry;
use Wakebit\CycleBridge\Contracts\Schema\CompilerInterface;
use Wakebit\CycleBridge\Contracts\Schema\GeneratorQueueInterface;

final class Compiler implements CompilerInterface
{
    private \Cycle\Schema\Compiler $compiler;

    public function __construct(private Registry $registry)
    {
        $this->compiler = new \Cycle\Schema\Compiler();
    }

    public function compile(GeneratorQueueInterface $queue): array
    {
        return $this->compiler->compile($this->registry, $queue->getGenerators());
    }
}
