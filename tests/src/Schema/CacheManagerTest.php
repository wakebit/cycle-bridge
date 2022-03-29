<?php

declare(strict_types=1);

namespace Wakebit\CycleBridge\Tests\Schema;

use Wakebit\CycleBridge\Contracts\Schema\CacheManagerInterface;
use Wakebit\CycleBridge\Tests\TestCase;

final class CacheManagerTest extends TestCase
{
    /** @var CacheManagerInterface */
    private $schemaCacheManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->schemaCacheManager = $this->container->get(CacheManagerInterface::class);
    }

    public function testWrite(): void
    {
        $schema = ['foo' => 'bar', 'john' => 'doe'];
        $this->schemaCacheManager->write($schema);

        $this->assertTrue($this->schemaCacheManager->isCached());
        $this->assertSame($schema, $this->schemaCacheManager->read());
    }

    public function testClear(): void
    {
        $this->schemaCacheManager->write(['bla' => 'bla']);
        $this->schemaCacheManager->clear();

        $this->assertFalse($this->schemaCacheManager->isCached());
        $this->assertNull($this->schemaCacheManager->read());
    }
}
