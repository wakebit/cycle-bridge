<?php

declare(strict_types=1);

namespace Wakebit\CycleBridge\Tests\Schema;

use Wakebit\CycleBridge\Contracts\Schema\CompilerInterface;
use Wakebit\CycleBridge\Contracts\Schema\GeneratorQueueInterface;
use Wakebit\CycleBridge\TestApp\Entity\Article;
use Wakebit\CycleBridge\TestApp\Entity\Customer;
use Wakebit\CycleBridge\Tests\TestCase;

final class CompilerTest extends TestCase
{
    public function testCompile(): void
    {
        $generatorQueue = $this->container->get(GeneratorQueueInterface::class);
        $schemaCompiler = $this->container->get(CompilerInterface::class);
        $output = $schemaCompiler->compile($generatorQueue);

        $this->assertCount(2, $output);
        $this->assertArrayHasKey('article', $output);
        $this->assertArrayHasKey('customer', $output);
        $this->assertSame(Article::class, $output['article'][1]);
        $this->assertSame(Customer::class, $output['customer'][1]);
    }
}
