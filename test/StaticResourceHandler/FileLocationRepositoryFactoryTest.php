<?php
/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole;

require_once('_MockIsDir.php');

use Mezzio\Swoole\StaticResourceHandler\FileLocationRepository;
use Mezzio\Swoole\StaticResourceHandler\FileLocationRepositoryFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class FileLocationRepositoryFactoryTest extends TestCase
{
    protected function setUp() : void
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->fileLocRepoFactory = new FileLocationRepositoryFactory();
    }

    public function testFactoryReturnsFileLocationRepository()
    {
        $factory = $this->fileLocRepoFactory;
        $fileLocRepo = $factory($this->container->reveal());
        $this->assertInstanceOf(FileLocationRepository::class, $fileLocRepo);
    }

    public function testFactoryUsesConfiguredDocumentRoot()
    {
        $dir = getcwd() . '/public/';
        $this->container->get('config')->willReturn([
            'mezzio-swoole' => [
                'swoole-http-server' =>  [
                    'static-files' => [
                        'document-root' => [$dir]
                    ]
                ]
            ]
        ]);
        $factory = $this->fileLocRepoFactory;
        $fileLocRepo = $factory($this->container->reveal());
        $this->assertEquals(['/' => [$dir]], $fileLocRepo->listMappedDocumentRoots());
    }

    public function testFactoryHasNoDefaultsIfEmptyDocumentRoot()
    {
        $dir = getcwd() . '/public/';
        $this->container->get('config')->willReturn([
            'mezzio-swoole' => [
                'swoole-http-server' =>  [
                    'static-files' => [
                        'document-root' => []
                    ]
                ]
            ]
        ]);
        $factory = $this->fileLocRepoFactory;
        $fileLocRepo = $factory($this->container->reveal());
        $this->assertEquals([], $fileLocRepo->listMappedDocumentRoots());
    }    

    public function testFactoryUsesDefaultDocumentRoot()
    {
        $dir = getcwd() . '/public/';
        $factory = $this->fileLocRepoFactory;
        $fileLocRepo = $factory($this->container->reveal());
        $this->assertEquals(['/' => [$dir]], $fileLocRepo->listMappedDocumentRoots());
    }
}
