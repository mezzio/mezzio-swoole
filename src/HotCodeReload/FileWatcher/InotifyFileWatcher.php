<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\HotCodeReload\FileWatcher;

use Mezzio\Swoole\Exception\ExtensionNotLoadedException;
use Mezzio\Swoole\Exception\RuntimeException;
use Mezzio\Swoole\HotCodeReload\FileWatcherInterface;
use Webmozart\Assert\Assert;

use function array_values;
use function extension_loaded;
use function in_array;
use function inotify_add_watch;
use function inotify_init;
use function inotify_read;
use function is_array;
use function is_dir;
use function scandir;
use function sprintf;
use function stream_set_blocking;

use const IN_MODIFY;

class InotifyFileWatcher implements FileWatcherInterface
{
    /** @var resource */
    private $inotify;

    /** @var string[] */
    private array $filePathByWd = [];

    public function __construct()
    {
        if (! extension_loaded('inotify')) {
            throw new ExtensionNotLoadedException('PHP extension "inotify" is required for this file watcher');
        }

        $resource = inotify_init();
        if (false === $resource) {
            throw new RuntimeException('Unable to initialize an inotify instance');
        }

        if (! stream_set_blocking($resource, false)) {
            throw new RuntimeException('Unable to set non-blocking mode on inotify stream');
        }

        $this->inotify = $resource;
    }

    /**
     * Add a file path to be monitored for changes by this watcher.
     */
    public function addFilePath(string $path): void
    {
        $paths = is_dir($path) ? $this->listSubdirectoriesRecursively($path) : [$path];
        foreach ($paths as $toWatch) {
            $wd                      = inotify_add_watch($this->inotify, $toWatch, IN_MODIFY);
            $this->filePathByWd[$wd] = $toWatch;
        }
    }

    /**
     * @psalm-return list<non-empty-string>
     */
    public function readChangedFilePaths(): array
    {
        $events = inotify_read($this->inotify);
        $paths  = [];
        if (is_array($events)) {
            foreach ($events as $event) {
                Assert::isArray($event);
                /** @var ?string $wd */
                $wd = $event['wd'] ?? null;
                if (null === $wd) {
                    throw new RuntimeException('Missing watch descriptor from inotify event');
                }

                $path = $this->filePathByWd[$wd] ?? null;
                if (null === $path) {
                    throw new RuntimeException(sprintf('Unrecognized watch descriptor: "%s"', $wd));
                }

                $paths[$path] = $path;
            }
        }

        $paths = array_values($paths);
        Assert::allStringNotEmpty($paths);

        return $paths;
    }

    /**
     * @psalm-param non-empty-string $path
     * @psalm-return list<non-empty-string>
     */
    private function listSubdirectoriesRecursively(string $path): array
    {
        $paths = [$path];

        foreach (scandir($path) as $file) {
            Assert::stringNotEmpty($file);

            if (in_array($file, ['.', '..'], true)) {
                // Skip current/parent directories
                continue;
            }

            $filename = $path . '/' . $file;
            if (! is_dir($filename)) {
                continue;
            }

            $paths = [...$paths, ...$this->listSubdirectoriesRecursively($filename)];
        }

        Assert::allStringNotEmpty($paths);

        return $paths;
    }
}
