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

class FileLocationRepositoryTest extends TestCase
{
    protected function setUp() : void
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->testDir = __DIR__;
        $this->testValDir = __DIR__ . '/';
        $this->fileLocRepo = new FileLocationRepository(['/' => $this->testValDir]);
    }

    public function testCanAddNewWithAddMappedRoot()
    {
        $this->fileLocRepo->addMappedDocumentRoot('/foo', $this->testDir);
        $this->assertEquals(
            ['/' => [$this->testValDir],
            '/foo/' => [$this->testValDir]],
            $this->fileLocRepo->listMappedDocumentRoots()
        );
    }

    public function testCanAppendWithAddMappedRoot()
    {
        $dir2 = __DIR__ . '/../';
        $this->fileLocRepo->addMappedDocumentRoot('/foo', $this->testDir);
        $this->fileLocRepo->addMappedDocumentRoot('/foo', $dir2);
        $this->assertEquals(
            ['/' => [$this->testValDir],
            '/foo/' => [$this->testValDir, $dir2]],
            $this->fileLocRepo->listMappedDocumentRoots()
        );
    }


    public function testNoDupeAddMappedRoot()
    {
        $this->fileLocRepo->addMappedDocumentRoot('/foo', $this->testDir);
        $this->fileLocRepo->addMappedDocumentRoot('/foo', $this->testDir);
        $this->assertEquals(
            ['/' => [$this->testValDir],
            '/foo/' => [$this->testValDir]],
            $this->fileLocRepo->listMappedDocumentRoots()
        );
    }

    public function testValidatePrefixReturnsSlashOnEmpty()
    {
        // Note - we are creating a temporary location to create a public folder,
        // since mocking is_dir and making phpcs happy at the same time
        // is problematic
        $cwd = \getcwd();
        $seed = time();
        $tmpDir = \sys_get_temp_dir();
        $tmpDir1 = $tmpDir . '/' . $seed;
        $tmpDir2 = $tmpDir1 . '/' . 'public';
        try {
            mkdir($tmpDir1);
            mkdir($tmpDir2);
            // validatePrefix called from addMappDocumentRoot
            $this->fileLocRepo->addMappedDocumentRoot('', $tmpDir2);
            $this->assertEquals(
                ['/' => [$this->testValDir, $tmpDir2 . '/']],
                $this->fileLocRepo->listMappedDocumentRoots()
            );
        } finally {
            \rmdir($tmpDir2);
            \rmdir($tmpDir1);
            \chdir($cwd);
        }
    }

    public function testValidatePrefixPrependsSlash()
    {
        // validatePrefix called from addMappDocumentRoot
        $dir = getcwd() . '/';
        $this->fileLocRepo->addMappedDocumentRoot('foo/', $this->testDir);
        $this->assertEquals(
            ['/' => [$this->testValDir],
            '/foo/' => [$this->testValDir]],
            $this->fileLocRepo->listMappedDocumentRoots()
        );
    }

    public function testValidatePrefixAppendsSlash()
    {
        // validatePrefix called from addMappDocumentRoot
        $dir = getcwd() . '/';
        $this->fileLocRepo->addMappedDocumentRoot('/foo', $this->testDir);
        $this->assertEquals(
            ['/' => [$this->testValDir],
            '/foo/' => [$this->testValDir]],
            $this->fileLocRepo->listMappedDocumentRoots()
        );
    }

    public function testValidateDirectoryReturnsIfDirectoryExists()
    {
        // validateDirectory called from addMappDocumentRoot
        $dir = getcwd();
        $this->fileLocRepo->addMappedDocumentRoot('/foo', $this->testDir);
        $this->assertEquals(
            ['/' => [$this->testValDir],
            '/foo/' => [$this->testValDir]],
            $this->fileLocRepo->listMappedDocumentRoots()
        );
    }

    public function testValidateDirectoryFaultsIfDirectoryExists()
    {
        // validateDirectory called from addMappDocumentRoot
        $this->expectException(\Exception::class);
        $msg = 'The document root for "/foo/", "BOGUS", does not exist; please check your configuration.';
        $this->expectExceptionMessage($msg);
        $this->fileLocRepo->addMappedDocumentRoot('/foo', 'BOGUS');
    }

    public function testValidatePDirctoryAppendsSlash()
    {
        // validatePrefix called from addMappDocumentRoot
        $dir = getcwd();
        $this->fileLocRepo->addMappedDocumentRoot('/foo', $this->testDir);
        $this->assertEquals(
            ['/' => [$this->testValDir], '/foo/' => [$this->testValDir]],
            $this->fileLocRepo->listMappedDocumentRoots()
        );
    }

    public function testListMappedDocumentRoots()
    {
        $this->assertEquals(
            ['/' => [$this->testValDir]],
            $this->fileLocRepo->listMappedDocumentRoots()
        );
    }

    public function testFindFileExists()
    {
        $dir = realpath(__DIR__ . '/../TestAsset');
        $full = realpath($dir . '/content.txt');
        // Add two directories for "test" so we can make sure non-matches are skipped
        $this->fileLocRepo->addMappedDocumentRoot('/test/', getcwd());
        $this->fileLocRepo->addMappedDocumentRoot('/test/', $dir);
        $this->assertEquals($full, $this->fileLocRepo->findFile('/test/content.txt'));
    }

    public function testFindFileDoesNotExist()
    {
        $this->assertEquals(null, $this->fileLocRepo->findFile('/foo'));
    }
}
