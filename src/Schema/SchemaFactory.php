<?php

declare(strict_types=1);

namespace Wakebit\CycleBridge\Schema;

use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Wakebit\CycleBridge\Contracts\Schema\CacheManagerInterface;
use Wakebit\CycleBridge\Contracts\Schema\CompilerInterface;
use Wakebit\CycleBridge\Contracts\Schema\GeneratorQueueInterface;
use Wakebit\CycleBridge\Schema\Config\SchemaConfig;

final class SchemaFactory
{
    /** @var CacheManagerInterface */
    private $cacheManager;

    /** @var CompilerInterface */
    private $compiler;

    /** @var GeneratorQueueInterface */
    private $generatorQueue;

    /** @var SchemaConfig */
    private $schemaConfig;

    public function __construct(
        CacheManagerInterface $cacheManager,
        CompilerInterface $compiler,
        GeneratorQueueInterface $generatorQueue,
        SchemaConfig $schemaConfig
    ) {
        $this->cacheManager = $cacheManager;
        $this->compiler = $compiler;
        $this->generatorQueue = $generatorQueue;
        $this->schemaConfig = $schemaConfig;
    }

    public function create(): SchemaInterface
    {
        if ($this->cacheManager->isCached()) {
            return new Schema($this->cacheManager->read() ?? []);
        }

        /** @var array|null $manuallyDefinedSchema */
        $manuallyDefinedSchema = $this->schemaConfig->getManuallyDefinedSchema();

        if ($manuallyDefinedSchema !== null) {
            return new Schema($manuallyDefinedSchema);
        }

        $schema = $this->compiler->compile($this->generatorQueue);

        return new Schema($schema);
    }
}
