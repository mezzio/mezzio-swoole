<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole;

use Mezzio\Swoole\ConfigProvider;
use Mezzio\Swoole\HotCodeReload\FileWatcher\InotifyFileWatcher;
use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    protected function setUp() : void
    {
        $this->provider = new ConfigProvider();
    }

    public function testInvocationReturnsArray()
    {
        $config = ($this->provider)();
        $this->assertIsArray($config);
        return $config;
    }

    /**
     * @depends testInvocationReturnsArray
     */
    public function testReturnedArrayContainsDependencies(array $config)
    {
        $this->assertArrayHasKey('dependencies', $config);
        $this->assertIsArray($config['dependencies']);
        return $config['dependencies'];
    }

    /**
     * @depends testReturnedArrayContainsDependencies
     * @see https://github.com/mezzio/mezzio-swoole/issues/11
     */
    public function testEnsureInotifyFileWatcherIsRegistered(array $dependencies): void
    {
        $this->assertArrayHasKey('invokables', $dependencies);
        $this->assertIsArray($dependencies['invokables']);
        $this->assertArrayHasKey(InotifyFileWatcher::class, $dependencies['invokables']);
        $this->assertSame(InotifyFileWatcher::class, $dependencies['invokables'][InotifyFileWatcher::class]);
    }
}
