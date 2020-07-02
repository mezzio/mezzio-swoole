<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole\StaticResourceHandler;

use InvalidArgumentException;

class FileLocationRepository implements FileLocationRepositoryInterface
{
    /**
     * @var array
     * Associative array of URI prefixes and directories
     */
    private $mappedDocRoots = [];

    /**
     * Initialize repository with default mapped document roots
     */
    public function __construct(array $mappedDocRoots)
    {
        // Set up any mapped document roots, validating prefixes and directories
        foreach ($mappedDocRoots as $prefix => $directory) {
            if (is_array($directory)) {
                foreach ($directory as $d) {
                    $this->addMappedDocumentRoot($prefix, $d);
                }
            } else {
                $this->addMappedDocumentRoot($prefix, $directory);
            }
        }
    }

    /**
     * Add the specified directory to list of mapped directories
     */
    public function addMappedDocumentRoot(string $prefix, string $directory): void
    {
        $valPrefix = $this->validatePrefix($prefix);
        $valDirectory = $this->validateDirectory($directory, $valPrefix);

        if (array_key_exists($valPrefix, $this->mappedDocRoots)) {
            $dirs = &$this->mappedDocRoots[$valPrefix];
            if (! in_array($valDirectory, $dirs)) {
                $dirs[] = $valDirectory;
            }
        } else {
            $this->mappedDocRoots[$valPrefix] = [$valDirectory];
        }
    }

    /**
     * Validate prefix, ensuring it is non-empty and starts and ends with a slash
     */
    private function validatePrefix(string $prefix): string
    {
        if (empty($prefix)) {
            // For the default prefix, set it to a slash to get matching to work
            $prefix = '/';
        } else {
            if ($prefix[0] != '/') {
                $prefix = "/$prefix";
            }
            if ($prefix[-1] != '/') {
                $prefix .= '/';
            }
        }
        return $prefix;
    }

    /**
     * Validate directory, ensuring it exists and
     */
    private function validateDirectory(string $directory, string $prefix): string
    {
        if (! is_dir($directory)) {
            throw new InvalidArgumentException(sprintf(
                'The document root for "%s", "%s", does not exist; please check your configuration.',
                empty($prefix) ? "(Default)" : $prefix,
                $directory
            ));
        }
        if ($directory[-1] != '/') {
            $directory .= '/';
        }
        return $directory;
    }

    /**
     * Return the mapped document roots
     */
    public function listMappedDocumentRoots(): array
    {
        return $this->mappedDocRoots;
    }

    /**
     * Searches for the specified file in mapped document root
     * directories; returns the location if found, or null if not
     */
    public function findFile(string $filename): ?string
    {
        foreach ($this->mappedDocRoots as $prefix => $directories) {
            foreach ($directories as $directory) {
                if (stripos($filename, $prefix) == 0) {
                    $mappedFileName = $directory . substr($filename, strlen($prefix));
                    if (file_exists($mappedFileName)) {
                        return $mappedFileName;
                    }
                }
            }
        }
        return null;
    }
}
