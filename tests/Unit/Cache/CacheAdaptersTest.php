<?php

declare(strict_types=1);

namespace Vima\Core\Tests\Unit\Cache;

use PHPUnit\Framework\TestCase;
use Vima\Core\Cache\Adapters\SymfonyCacheAdapter;
use Vima\Core\Cache\Adapters\NullCache;

class CacheAdaptersTest extends TestCase
{
    public function testNullCacheActsAsBlackHole()
    {
        $cache = new NullCache();
        
        $this->assertTrue($cache->set('foo', 'bar'));
        $this->assertNull($cache->get('foo'));
        $this->assertEquals('default', $cache->get('foo', 'default'));
        $this->assertTrue($cache->delete('foo'));
        $this->assertTrue($cache->clear());
    }

    public function testSymfonyCacheAdapterStoresAndRetrieves()
    {
        $cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'vima_test_cache';
        $cache = new SymfonyCacheAdapter($cacheDir);

        // Ensure clean state
        $cache->clear();

        $this->assertNull($cache->get('test_key'));
        
        $this->assertTrue($cache->set('test_key', 'test_value'));
        $this->assertEquals('test_value', $cache->get('test_key'));

        $this->assertTrue($cache->delete('test_key'));
        $this->assertNull($cache->get('test_key'));

        // Cleanup
        $cache->clear();
    }
}
