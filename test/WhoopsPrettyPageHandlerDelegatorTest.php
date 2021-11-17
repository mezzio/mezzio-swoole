<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole;

use Laminas\ServiceManager\ServiceManager;
use Mezzio\Container\WhoopsPageHandlerFactory;
use Mezzio\Swoole\ConfigProvider;
use PHPUnit\Framework\TestCase;
use Whoops\Handler\PrettyPageHandler;

class WhoopsPrettyPageHandlerDelegatorTest extends TestCase
{
    /** @var ServiceManager */
    private $container;

    protected function setUp(): void
    {
        /** @psalm-var array<array-key, array<array-key, string|callable>> $dependencies */
        $dependencies = (new ConfigProvider())()['dependencies'];
        // @see https://github.com/mezzio/mezzio-skeleton/blob/master/src/MezzioInstaller/Resources/config/error-handler-whoops.php
        $dependencies['factories']['Mezzio\WhoopsPageHandler'] = WhoopsPageHandlerFactory::class;

        /** @psalm-suppress InvalidArgument */
        $this->container = new ServiceManager($dependencies);
    }

    public function testDefaultConfigurationDecoratesPageHandler(): void
    {
        $handler = $this->container->get('Mezzio\WhoopsPageHandler');
        $this->assertInstanceOf(PrettyPageHandler::class, $handler);
        $this->assertTrue($handler->handleUnconditionally());
    }
}
