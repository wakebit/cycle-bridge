<?php

declare(strict_types=1);

namespace Wakebit\CycleBridge\Schema;

use Cycle\Schema\GeneratorInterface;
use Psr\Container\ContainerInterface;
use Wakebit\CycleBridge\Contracts\Schema\GeneratorQueueInterface;
use Wakebit\CycleBridge\Schema\Config\SchemaConfig;

final class GeneratorQueue implements GeneratorQueueInterface
{
    /** @var ContainerInterface */
    private $container;

    /** @var array<array<GeneratorInterface|class-string<GeneratorInterface>>> */
    private $generators;

    public function __construct(ContainerInterface $container, SchemaConfig $schemaConfig)
    {
        $this->container = $container;
        $this->generators = $schemaConfig->getGenerators();
    }

    /** {@inheritdoc} */
    public function addGenerator(string $group, $generator): GeneratorQueueInterface
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
     * @param string|GeneratorInterface $generator
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function assemblyGenerator($generator): GeneratorInterface
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
