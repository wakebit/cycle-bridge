<?php

declare(strict_types=1);

namespace Wakebit\CycleBridge\Schema\Config;

use Cycle\Schema\GeneratorInterface;
use Spiral\Core\InjectableConfig;
use Wakebit\CycleBridge\Contracts\Schema\GeneratorQueueInterface;

final class SchemaConfig extends InjectableConfig
{
    public function getCacheStore(): mixed
    {
        return $this->config['cache']['store'] ?? 'file';
    }

    public function getManuallyDefinedSchema(): mixed
    {
        return $this->config['map'] ?? null;
    }

    /**
     * @return array<array<GeneratorInterface|class-string<GeneratorInterface>>>
     */
    public function getGenerators(): array
    {
        $defaultGeneratorQueue = [
            GeneratorQueueInterface::GROUP_INDEX => [
                \Cycle\Annotated\Embeddings::class,                 // register embeddable entities
                \Cycle\Annotated\Entities::class,                   // register annotated entities
                \Cycle\Annotated\MergeColumns::class,               // add @Table column declarations
            ],
            GeneratorQueueInterface::GROUP_RENDER => [
                \Cycle\Schema\Generator\ResetTables::class,         // re-declared table schemas (remove columns)
                \Cycle\Schema\Generator\GenerateRelations::class,   // generate entity relations
                \Cycle\Schema\Generator\ValidateEntities::class,    // make sure all entity schemas are correct
                \Cycle\Schema\Generator\RenderTables::class,        // declare table schemas
                \Cycle\Schema\Generator\RenderRelations::class,     // declare relation keys and indexes
                \Cycle\Annotated\MergeIndexes::class,               // add @Table column declarations
            ],
            GeneratorQueueInterface::GROUP_POSTPROCESS => [
                \Cycle\Schema\Generator\GenerateTypecast::class,    // typecast non string columns
            ],
        ];

        /** @var array<array<GeneratorInterface|class-string<GeneratorInterface>>> */
        return $this->config['generators'] ?? $defaultGeneratorQueue;
    }
}
