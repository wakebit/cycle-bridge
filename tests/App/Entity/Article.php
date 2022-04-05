<?php

declare(strict_types=1);

namespace Wakebit\CycleBridge\TestApp\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

/**
 * @psalm-suppress MissingConstructor
 */
#[Entity]
final class Article
{
    #[Column(type: 'primary')]
    private int $id;

    #[Column(type: 'string')]
    private string $title;

    #[Column(type: 'string')]
    private string $description;
}
