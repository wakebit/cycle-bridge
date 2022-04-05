<?php

declare(strict_types=1);

namespace Wakebit\CycleBridge\Schema;

use Cycle\Schema\GeneratorInterface;
use Psr\Container\ContainerInterface;
use Wakebit\CycleBridge\Contracts\Schema\GeneratorQueueInterface;
use Wakebit\CycleBridge\Schema\Config\SchemaConfig;

final class GeneratorQueue implements GeneratorQueueInterface
{
    /** @var array<array<GeneratorInterface|class-string<GeneratorInterface>>> */
    private array $generators;

    public function __construct(private ContainerInterface $container, SchemaConfig $schemaConfig)
    {
        $this->generators = $schemaConfig->getGenerators();
    }

    /** {@inheritdoc} */
    public function addGenerator(string $group, GeneratorInterface|string $generator): GeneratorQueueInterface
    {
        $queue = clone $this;

        $queue->generators[$group][] = $generator;

        return $queue;
    }

    /** {@inheritdoc} */
    public function removeGenerator(string $removableGenerator): GeneratorQueueInterface
    {
        $queue = clone $this;

        foreach ($queue->generators as $groupKey => $groupName) {
            foreach ($groupName as $generatorKey => $generatorDefinition) {
                if (is_a($generatorDefinition, $removableGenerator, true)) {
                    unset($queue->generators[$groupKey][$generatorKey]);
                }
            }
        }

        return $queue;
    }

    /** {@inheritdoc} */
    public function getGenerators(): array
    {
        $result = [];

        foreach ($this->generators as $group) {
            foreach ($group as $generator) {
                $result[] = $this->assemblyGenerator($generator);
            }
        }

        return $result;
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function assemblyGenerator(GeneratorInterface|string $generator): GeneratorInterface
    {
        if ($generator instanceof GeneratorInterface) {
            return $generator;
        }

        /** @var GeneratorInterface */
        return $this->container->get($generator);
    }

    public function withoutGenerators(): GeneratorQueueInterface
    {
        $queue = clone $this;

        $queue->generators = [];

        return $queue;
    }
}
