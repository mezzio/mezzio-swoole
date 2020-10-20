<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole;

use Mezzio\Swoole\StaticResourceHandler\FileLocationRepository;
use Mezzio\Swoole\StaticResourceHandler\FileLocationRepositoryFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

use function chdir;
use function getcwd;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function time;

class FileLocationRepositoryFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        $this->mockContainer      = $this->createMock(ContainerInterface::class);
        $this->fileLocRepoFactory = new FileLocationRepositoryFactory();
        $this->assetDir           = __DIR__ . '/../TestAsset';
    }

    public function testFactoryReturnsFileLocationRepository()
    {
        $this->mockContainer->method('get')->with('config')->willReturn([
            'mezzio-swoole' => [
                'swoole-http-server' => [
                    'static-files' => [
                        'document-root' => [],
                    ],
                ],
            ],
        ]);
        $factory     = $this->fileLocRepoFactory;
        $fileLocRepo = $factory($this->mockContainer);
        $this->assertInstanceOf(FileLocationRepository::class, $fileLocRepo);
    }

    public function testFactoryUsesConfiguredDocumentRootArray()
    {
        $this->mockContainer->method('get')->with('config')->willReturn([
            'mezzio-swoole' => [
                'swoole-http-server' => [
                    'static-files' => [
                        'document-root' => [$this->assetDir],
                    ],
                ],
            ],
        ]);
        $factory     = $this->fileLocRepoFactory;
        $fileLocRepo = $factory($this->mockContainer);
        $this->assertEquals(['/' => [$this->assetDir . '/']], $fileLocRepo->listMappedDocumentRoots());
    }

    public function testFactoryUsesConfiguredMappedDocumentRootsArray()
    {
        $this->mockContainer->method('get')->with('config')->willReturn([
            'mezzio-swoole' => [
                'swoole-http-server' => [
                    'static-files' => [
                        'document-root'         => [],
                        'mapped-document-roots' => [
                            'foo' => [$this->assetDir],
                        ],
                    ],
                ],
            ],
        ]);
        $factory     = $this->fileLocRepoFactory;
        $fileLocRepo = $factory($this->mockContainer);
        $this->assertEquals(['/foo/' => [$this->assetDir . '/']], $fileLocRepo->listMappedDocumentRoots());
    }

    public function testFactoryUsesConfiguredMappedDocumentRootString()
    {
        $this->mockContainer->method('get')->with('config')->willReturn([
            'mezzio-swoole' => [
                'swoole-http-server' => [
                    'static-files' => [
                        'document-root'         => [],
                        'mapped-document-roots' => [
                            'foo' => [$this->assetDir],
                        ],
                    ],
                ],
            ],
        ]);
        $factory     = $this->fileLocRepoFactory;
        $fileLocRepo = $factory($this->mockContainer);

        $this->assertEquals(['/foo/' => [$this->assetDir . '/']], $fileLocRepo->listMappedDocumentRoots());
    }

    public function testFactoryUsesBothConfiguredRootAndMappedDocumentRootString()
    {
        $this->mockContainer->method('get')->with('config')->willReturn([
            'mezzio-swoole' => [
                'swoole-http-server' => [
                    'static-files' => [
                        'document-root'         => __DIR__,
                        'mapped-document-roots' => [
                            'foo' => [$this->assetDir],
                        ],
                    ],
                ],
            ],
        ]);
        $factory     = $this->fileLocRepoFactory;
        $fileLocRepo = $factory($this->mockContainer);

        $this->assertEquals(
            ['/' => [__DIR__ . '/'], '/foo/' => [$this->assetDir . '/']],
            $fileLocRepo->listMappedDocumentRoots()
        );
    }

    public function testFactoryUsesConfiguredDocumentRootString()
    {
        $this->mockContainer->method('get')->with('config')->willReturn([
            'mezzio-swoole' => [
                'swoole-http-server' => [
                    'static-files' => [
                        'document-root' => $this->assetDir,
                    ],
                ],
            ],
        ]);
        $factory     = $this->fileLocRepoFactory;
        $fileLocRepo = $factory($this->mockContainer);

        $this->assertEquals(['/' => [$this->assetDir . '/']], $fileLocRepo->listMappedDocumentRoots());
    }

    public function testFactoryHasNoDefaultsIfEmptyDocumentRoot()
    {
        $this->mockContainer->method('get')->with('config')->willReturn([
            'mezzio-swoole' => [
                'swoole-http-server' => [
                    'static-files' => [
                        'document-root' => [],
                    ],
                ],
            ],
        ]);
        $factory     = $this->fileLocRepoFactory;
        $fileLocRepo = $factory($this->mockContainer);
        $this->assertEquals([], $fileLocRepo->listMappedDocumentRoots());
    }

    public function testFactoryUsesDefaultDocumentRoot()
    {
        // Note - we are creating a temporary location to create a public folder,
        // since mocking is_dir and making phpcs happy at the same time
        // is problematic
        $cwd     = getcwd();
        $seed    = time();
        $tmpDir  = sys_get_temp_dir();
        $tmpDir1 = $tmpDir . '/' . $seed;
        $tmpDir2 = $tmpDir1 . '/public';
        try {
            mkdir($tmpDir1);
            mkdir($tmpDir2);
            chdir($tmpDir1);
            $this->mockContainer->method('get')->with('config')->willReturn([
                'mezzio-swoole' => [
                    'swoole-http-server' => [
                        'static-files' => [],
                    ],
                ],
            ]);
            $factory     = $this->fileLocRepoFactory;
            $fileLocRepo = $factory($this->mockContainer);
            $this->assertEquals(['/' => [$tmpDir2 . '/']], $fileLocRepo->listMappedDocumentRoots());
        } finally {
            rmdir($tmpDir2);
            rmdir($tmpDir1);
            chdir($cwd);
        }
    }
}
