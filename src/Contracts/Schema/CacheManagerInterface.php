<?php

declare(strict_types=1);

namespace Wakebit\CycleBridge\Contracts\Schema;

interface CacheManagerInterface
{
    public function isCached(): bool;

    public function read(): ?array;

    public function write(array $schema): void;

    public function clear(): void;
}
