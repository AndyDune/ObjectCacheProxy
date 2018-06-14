<?php
/**
 *
 * PHP version >= 7.1
 *
 * @package andydune/object-cache-proxy
 * @link  https://github.com/AndyDune/ObjectCacheProxy for the canonical source repository
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrey Ryzhov  <info@rznw.ru>
 * @copyright 2018 Andrey Ryzhov
 */

namespace AndyDuneTest\ObjectCacheProxy;

use AndyDune\ObjectCacheProxy\Example\Length;
use AndyDune\ObjectCacheProxy\ObjectCacheProxy;
use PHPUnit\Framework\TestCase;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Symfony\Component\Cache\Simple\FilesystemCache;

class ObjectCacheProxyTest extends TestCase
{
    public function testProxy()
    {
        $cache = new ObjectCacheProxy();
        $cache->setAllow(false);
        $cache->setObject($this->getObjectForTest(), 'execute');

        $cache->setLength(5);
        $result = $cache->execute();
        $this->assertCount(5, $result);
        $this->assertEquals(5, $cache->getLengthDone());
    }


    public function testCache()
    {
        $dir = __DIR__;

        $tempDir = new TemporaryDirectory($dir);
        $tempDir->name('cache');
        $tempDir->empty();
        $cacheAdapter = new FilesystemCache('test', 3600, $tempDir->path());

        $cache = new ObjectCacheProxy($cacheAdapter);
        $cache->setObject($this->getObjectForTest(), 'execute');

        $cache->setLength(5);
        $result = $cache->execute();
        $this->assertCount(5, $result);
        $cache->setAllow(false);
        $this->assertEquals(5, $cache->getLengthDone()); // context worked
        $cache->clean();

        $cache->setAllow(true);

        $cache->setLength(5);
        $result = $cache->execute();
        $this->assertCount(5, $result);
        $cache->setAllow(false);
        $this->assertEquals(0, $cache->getLengthDone()); // context didn't work - data from cache

        $tempDir->empty();

        $cache = new ObjectCacheProxy($cacheAdapter);
        $cache->setObject($this->getObjectForTest(), 'execute');
        $cache->setCacheKeyMethods('noExist');
        $cache->setLength(5);
        $result = $cache->execute();
        $this->assertCount(5, $result);
        $this->assertEquals(5, $cache->getLengthDone()); // context worked

        $cache = new ObjectCacheProxy($cacheAdapter);
        $cache->setObject($this->getObjectForTest(), 'execute');
        $cache->setCacheKeyMethods('noExist');
        $cache->setLength(50);
        $result = $cache->execute();
        $this->assertCount(5, $result);
        $this->assertEquals(0, $cache->getLengthDone()); // context worked


        $cache = new ObjectCacheProxy($cacheAdapter);
        $cache->setObject($this->getObjectForTest(), 'execute');
        $cache->setCacheKeyMethods('setLength');
        $cache->setLength(6);
        $result = $cache->execute();
        $this->assertCount(6, $result);
        $this->assertEquals(6, $cache->getLengthDone()); // context worked

        $cache = new ObjectCacheProxy($cacheAdapter);
        $cache->setObject($this->getObjectForTest(), 'execute');
        $cache->setCacheKeyMethods('setLength');
        $cache->setLength(6);
        $result = $cache->execute();
        $this->assertCount(6, $result);
        $this->assertEquals(0, $cache->getLengthDone()); // context worked


        $tempDir->delete();
    }


    protected function getObjectForTest()
    {
        return new Length();
    }
}