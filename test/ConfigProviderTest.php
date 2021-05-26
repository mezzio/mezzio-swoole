<?php

declare(strict_types=1);

namespace MezzioTest\Swoole;

use Mezzio\Swoole\ConfigProvider;
use Mezzio\Swoole\HotCodeReload\FileWatcher\InotifyFileWatcher;
use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    /** @var ConfigProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->provider = new ConfigProvider();
    }

    public function testReturnedArrayContainsDependencies(): array
    {
        $config = ($this->provider)();
        $this->assertArrayHasKey('dependencies', $config);
        $this->assertIsArray($config['dependencies']);
        return $config['dependencies'];
    }

    /**
     * @see https://github.com/mezzio/mezzio-swoole/issues/11
     *
     * @depends testReturnedArrayContainsDependencies
     */
    public function testEnsureInotifyFileWatcherIsRegistered(array $dependencies): void
    {
        $this->assertArrayHasKey('invokables', $dependencies);
        $this->assertIsArray($dependencies['invokables']);
        $this->assertArrayHasKey(InotifyFileWatcher::class, $dependencies['invokables']);
        $this->assertSame(InotifyFileWatcher::class, $dependencies['invokables'][InotifyFileWatcher::class]);
    }
}
