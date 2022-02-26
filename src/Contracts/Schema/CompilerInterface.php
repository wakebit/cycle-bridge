<?php

declare(strict_types=1);

namespace Wakebit\CycleBridge\Contracts\Schema;

interface CompilerInterface
{
    public function compile(GeneratorQueueInterface $queue): array;
}
