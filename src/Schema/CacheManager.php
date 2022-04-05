<?php

declare(strict_types=1);

namespace Wakebit\CycleBridge\Schema;

use Psr\SimpleCache\CacheInterface;
use Wakebit\CycleBridge\Contracts\Schema\CacheManagerInterface;

final class CacheManager implements CacheManagerInterface
{
    private const SCHEMA_CACHE_KEY = 'cycle.orm.schema';

    public function __construct(private CacheInterface $cache)
    {
    }

    public function isCached(): bool
    {
        return $this->cache->has(self::SCHEMA_CACHE_KEY);
    }

    public function read(): ?array
    {
        /** @var array|null */
        return $this->cache->get(self::SCHEMA_CACHE_KEY);
    }

    public function write(array $schema): void
    {
        $this->cache->set(self::SCHEMA_CACHE_KEY, $schema);
    }

    public function clear(): void
    {
        $this->cache->delete(self::SCHEMA_CACHE_KEY);
    }
}
