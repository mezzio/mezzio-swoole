<?php

declare(strict_types=1);

namespace MezzioTest\Swoole\HotCodeReload\FileWatcher;

use Mezzio\Swoole\HotCodeReload\FileWatcher\InotifyFileWatcher;
use PHPUnit\Framework\TestCase;

use function extension_loaded;
use function fclose;
use function fwrite;
use function stream_get_meta_data;
use function tmpfile;

class InotifyFileWatcherTest extends TestCase
{
    /** @var resource */
    private $file;

    protected function setUp(): void
    {
        if (! extension_loaded('inotify')) {
            static::markTestSkipped('The Inotify extension is not available');
        }

        $file = tmpfile();
        if (false === $file) {
            static::markTestSkipped('Unable to create a temporary file');
        }
        $this->file = $file;

        parent::setUp();
    }

    protected function tearDown(): void
    {
        fclose($this->file);
        parent::tearDown();
    }

    public function testReadChangedFilePathsIsNonBlocking(): void
    {
        /** @psalm-var non-empty-string $path */
        $path    = stream_get_meta_data($this->file)['uri'];
        $subject = new InotifyFileWatcher();
        $subject->addFilePath($path);

        static::assertEmpty($subject->readChangedFilePaths());
        fwrite($this->file, 'foo');
        static::assertEquals([$path], $subject->readChangedFilePaths());
    }
}
