<?php

declare(strict_types=1);

namespace MezzioTest\Swoole\StaticResourceHandler;

use Exception;
use Mezzio\Swoole\StaticResourceHandler\FileLocationRepository;
use PHPUnit\Framework\TestCase;

use function chdir;
use function getcwd;
use function mkdir;
use function realpath;
use function rmdir;
use function sys_get_temp_dir;
use function time;

class FileLocationRepositoryTest extends TestCase
{
    /**
     * @var string
     * @psalm-var non-empty-string
     */
    private $testDir;

    /**
     * @var string
     * @psalm-var non-empty-string
     */
    private $testValDir;

    /** @var FileLocationRepository */
    private $fileLocRepo;

    protected function setUp(): void
    {
        $this->testDir     = __DIR__;
        $this->testValDir  = __DIR__ . '/';
        $this->fileLocRepo = new FileLocationRepository(['/' => $this->testValDir]);
    }

    public function testCanAddNewWithAddMappedRoot(): void
    {
        $this->fileLocRepo->addMappedDocumentRoot('/foo', $this->testDir);
        $this->assertEquals(
            [
                '/'     => [$this->testValDir],
                '/foo/' => [$this->testValDir],
            ],
            $this->fileLocRepo->listMappedDocumentRoots()
        );
    }

    public function testCanAppendWithAddMappedRoot(): void
    {
        $dir2 = __DIR__ . '/../';
        $this->fileLocRepo->addMappedDocumentRoot('/foo', $this->testDir);
        $this->fileLocRepo->addMappedDocumentRoot('/foo', $dir2);
        $this->assertEquals(
            [
                '/'     => [$this->testValDir],
                '/foo/' => [$this->testValDir, $dir2],
            ],
            $this->fileLocRepo->listMappedDocumentRoots()
        );
    }

    public function testNoDupeAddMappedRoot(): void
    {
        $this->fileLocRepo->addMappedDocumentRoot('/foo', $this->testDir);
        $this->fileLocRepo->addMappedDocumentRoot('/foo', $this->testDir);
        $this->assertEquals(
            [
                '/'     => [$this->testValDir],
                '/foo/' => [$this->testValDir],
            ],
            $this->fileLocRepo->listMappedDocumentRoots()
        );
    }

    public function testValidatePrefixReturnsSlashOnEmpty(): void
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
            // validatePrefix called from addMappDocumentRoot
            $this->fileLocRepo->addMappedDocumentRoot('', $tmpDir2);
            $this->assertEquals(
                ['/' => [$this->testValDir, $tmpDir2 . '/']],
                $this->fileLocRepo->listMappedDocumentRoots()
            );
        } finally {
            rmdir($tmpDir2);
            rmdir($tmpDir1);
            chdir($cwd);
        }
    }

    public function testValidatePrefixPrependsSlash(): void
    {
        // validatePrefix called from addMappDocumentRoot
        getcwd() . '/';
        $this->fileLocRepo->addMappedDocumentRoot('foo/', $this->testDir);
        $this->assertEquals(
            [
                '/'     => [$this->testValDir],
                '/foo/' => [$this->testValDir],
            ],
            $this->fileLocRepo->listMappedDocumentRoots()
        );
    }

    public function testValidatePrefixAppendsSlash(): void
    {
        // validatePrefix called from addMappDocumentRoot
        getcwd() . '/';
        $this->fileLocRepo->addMappedDocumentRoot('/foo', $this->testDir);
        $this->assertEquals(
            [
                '/'     => [$this->testValDir],
                '/foo/' => [$this->testValDir],
            ],
            $this->fileLocRepo->listMappedDocumentRoots()
        );
    }

    public function testValidateDirectoryReturnsIfDirectoryExists(): void
    {
        // validateDirectory called from addMappDocumentRoot
        getcwd();
        $this->fileLocRepo->addMappedDocumentRoot('/foo', $this->testDir);
        $this->assertEquals(
            [
                '/'     => [$this->testValDir],
                '/foo/' => [$this->testValDir],
            ],
            $this->fileLocRepo->listMappedDocumentRoots()
        );
    }

    public function testValidateDirectoryFaultsIfDirectoryExists(): void
    {
        // validateDirectory called from addMappDocumentRoot
        $this->expectException(Exception::class);
        $msg = 'The document root for "/foo/", "BOGUS", does not exist; please check your configuration.';
        $this->expectExceptionMessage($msg);
        $this->fileLocRepo->addMappedDocumentRoot('/foo', 'BOGUS');
    }

    public function testValidatePDirctoryAppendsSlash(): void
    {
        // validatePrefix called from addMappDocumentRoot
        getcwd();
        $this->fileLocRepo->addMappedDocumentRoot('/foo', $this->testDir);
        $this->assertEquals(
            ['/' => [$this->testValDir], '/foo/' => [$this->testValDir]],
            $this->fileLocRepo->listMappedDocumentRoots()
        );
    }

    public function testListMappedDocumentRoots(): void
    {
        $this->assertEquals(
            ['/' => [$this->testValDir]],
            $this->fileLocRepo->listMappedDocumentRoots()
        );
    }

    public function testFindFileExists(): void
    {
        $dir  = realpath(__DIR__ . '/../TestAsset');
        $full = realpath($dir . '/content.txt');
        // Add two directories for "test" so we can make sure non-matches are skipped
        $this->fileLocRepo->addMappedDocumentRoot('/test/', getcwd());
        $this->fileLocRepo->addMappedDocumentRoot('/test/', $dir);
        $this->assertEquals($full, $this->fileLocRepo->findFile('/test/content.txt'));
    }

    public function testFindFileDoesNotExist(): void
    {
        $this->assertEquals(null, $this->fileLocRepo->findFile('/foo'));
    }
}
