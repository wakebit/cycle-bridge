<?php

declare(strict_types=1);

namespace Wakebit\CycleBridge\Contracts\Schema;

use Cycle\Schema\GeneratorInterface;

interface GeneratorQueueInterface
{
    public const GROUP_INDEX = 'index';
    public const GROUP_RENDER = 'render';
    public const GROUP_POSTPROCESS = 'postprocess';

    /**
     * @param self::GROUP_*                                       $group
     * @param GeneratorInterface|class-string<GeneratorInterface> $generator
     *
     * @return $this
     */
    public function addGenerator(string $group, $generator): self;

    /**
     * @param class-string<GeneratorInterface> $removableGenerator
     *
     * @return $this
     */
    public function removeGenerator(string $removableGenerator): self;

    /**
     * @return array<GeneratorInterface>
     */
    public function getGenerators(): array;

    public function withoutGenerators(): self;
}
