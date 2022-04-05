<?php

declare(strict_types=1);

namespace Wakebit\CycleBridge\Tests\Schema;

use Cycle\Schema\GeneratorInterface;
use Wakebit\CycleBridge\Contracts\Schema\GeneratorQueueInterface;
use Wakebit\CycleBridge\Tests\TestCase;

final class GeneratorQueueTest extends TestCase
{
    private GeneratorQueueInterface $generatorQueue;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generatorQueue = $this->container->get(GeneratorQueueInterface::class);
    }

    public function testGetGenerators(): void
    {
        $generators = $this->generatorQueue->getGenerators();

        $this->assertBaseGenerators($generators);
    }

    public function testAddGenerator(): void
    {
        $generators = $this->generatorQueue
            ->addGenerator(GeneratorQueueInterface::GROUP_RENDER, \Cycle\Schema\Generator\SyncTables::class)
            ->getGenerators();

        $this->assertBaseGenerators($this->generatorQueue->getGenerators());

        $this->assertCount(14, $generators);
        $this->assertInstanceOf(\Cycle\Annotated\Embeddings::class, $generators[0]);
        $this->assertInstanceOf(\Cycle\Annotated\Entities::class, $generators[1]);
        $this->assertInstanceOf(\Cycle\Annotated\TableInheritance::class, $generators[2]);
        $this->assertInstanceOf(\Cycle\Annotated\MergeColumns::class, $generators[3]);
        $this->assertInstanceOf(\Cycle\Schema\Generator\ResetTables::class, $generators[4]);
        $this->assertInstanceOf(\Cycle\Schema\Generator\GenerateRelations::class, $generators[5]);
        $this->assertInstanceOf(\Cycle\Schema\Generator\GenerateModifiers::class, $generators[6]);
        $this->assertInstanceOf(\Cycle\Schema\Generator\ValidateEntities::class, $generators[7]);
        $this->assertInstanceOf(\Cycle\Schema\Generator\RenderTables::class, $generators[8]);
        $this->assertInstanceOf(\Cycle\Schema\Generator\RenderRelations::class, $generators[9]);
        $this->assertInstanceOf(\Cycle\Schema\Generator\RenderModifiers::class, $generators[10]);
        $this->assertInstanceOf(\Cycle\Annotated\MergeIndexes::class, $generators[11]);
        $this->assertInstanceOf(\Cycle\Schema\Generator\SyncTables::class, $generators[12]);
        $this->assertInstanceOf(\Cycle\Schema\Generator\GenerateTypecast::class, $generators[13]);
    }

    public function testRemoveGenerator(): void
    {
        $generators = $this->generatorQueue
            ->removeGenerator(\Cycle\Annotated\Embeddings::class)
            ->removeGenerator(\Cycle\Annotated\Entities::class)
            ->removeGenerator(\Cycle\Annotated\TableInheritance::class)
            ->removeGenerator(\Cycle\Annotated\MergeColumns::class)
            ->removeGenerator(\Cycle\Annotated\MergeIndexes::class)
            ->getGenerators();

        $this->assertBaseGenerators($this->generatorQueue->getGenerators());

        $this->assertCount(8, $generators);
        $this->assertInstanceOf(\Cycle\Schema\Generator\ResetTables::class, $generators[0]);
        $this->assertInstanceOf(\Cycle\Schema\Generator\GenerateRelations::class, $generators[1]);
        $this->assertInstanceOf(\Cycle\Schema\Generator\GenerateModifiers::class, $generators[2]);
        $this->assertInstanceOf(\Cycle\Schema\Generator\ValidateEntities::class, $generators[3]);
        $this->assertInstanceOf(\Cycle\Schema\Generator\RenderTables::class, $generators[4]);
        $this->assertInstanceOf(\Cycle\Schema\Generator\RenderRelations::class, $generators[5]);
        $this->assertInstanceOf(\Cycle\Schema\Generator\RenderModifiers::class, $generators[6]);
        $this->assertInstanceOf(\Cycle\Schema\Generator\GenerateTypecast::class, $generators[7]);
    }

    public function testWithoutGenerators(): void
    {
        $generators = $this->generatorQueue
            ->withoutGenerators()
            ->getGenerators();

        $this->assertBaseGenerators($this->generatorQueue->getGenerators());
        $this->assertCount(0, $generators);
    }

    /**
     * @param array<GeneratorInterface> $generators
     */
    private function assertBaseGenerators(array $generators): void
    {
        $this->assertCount(13, $generators);
        $this->assertInstanceOf(\Cycle\Annotated\Embeddings::class, $generators[0]);
        $this->assertInstanceOf(\Cycle\Annotated\Entities::class, $generators[1]);
        $this->assertInstanceOf(\Cycle\Annotated\TableInheritance::class, $generators[2]);
        $this->assertInstanceOf(\Cycle\Annotated\MergeColumns::class, $generators[3]);
        $this->assertInstanceOf(\Cycle\Schema\Generator\ResetTables::class, $generators[4]);
        $this->assertInstanceOf(\Cycle\Schema\Generator\GenerateRelations::class, $generators[5]);
        $this->assertInstanceOf(\Cycle\Schema\Generator\GenerateModifiers::class, $generators[6]);
        $this->assertInstanceOf(\Cycle\Schema\Generator\ValidateEntities::class, $generators[7]);
        $this->assertInstanceOf(\Cycle\Schema\Generator\RenderTables::class, $generators[8]);
        $this->assertInstanceOf(\Cycle\Schema\Generator\RenderRelations::class, $generators[9]);
        $this->assertInstanceOf(\Cycle\Schema\Generator\RenderModifiers::class, $generators[10]);
        $this->assertInstanceOf(\Cycle\Annotated\MergeIndexes::class, $generators[11]);
        $this->assertInstanceOf(\Cycle\Schema\Generator\GenerateTypecast::class, $generators[12]);
    }
}
