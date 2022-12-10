<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\StaticResourceHandler;

use InvalidArgumentException;

use function array_key_exists;
use function file_exists;
use function in_array;
use function is_array;
use function is_dir;
use function rtrim;
use function sprintf;
use function stripos;
use function strlen;
use function substr;
use function trim;

class FileLocationRepository implements FileLocationRepositoryInterface
{
    /**
     * Associative array of URI prefixes and directories
     */
    private array $mappedDocRoots = [];

    /**
     * Initialize repository with default mapped document roots
     */
    public function __construct(array $mappedDocRoots)
    {
        // Set up any mapped document roots, validating prefixes and directories
        foreach ($mappedDocRoots as $prefix => $directory) {
            if (! is_array($directory)) {
                $this->addMappedDocumentRoot($prefix, $directory);

                continue;
            }

            foreach ($directory as $d) {
                $this->addMappedDocumentRoot($prefix, $d);
            }
        }
    }

    /**
     * Add the specified directory to list of mapped directories
     */
    public function addMappedDocumentRoot(string $prefix, string $directory): void
    {
        $prefix    = $this->normalizePrefix($prefix);
        $directory = $this->normalizeDirectory($directory, $prefix);

        if (! array_key_exists($prefix, $this->mappedDocRoots)) {
            $this->mappedDocRoots[$prefix] = [$directory];

            return;
        }

        if (! in_array($directory, $this->mappedDocRoots[$prefix], true)) {
            $this->mappedDocRoots[$prefix][] = $directory;
        }
    }

    /**
     * Normalize prefix, ensuring it is non-empty and starts and ends with a slash
     */
    private function normalizePrefix(string $prefix): string
    {
        $prefix = trim($prefix, '/');

        if (empty($prefix)) {
            // For the default prefix, set it to a slash to get matching to work
            return '/';
        }

        return sprintf('/%s/', $prefix);
    }

    /**
     * Normalize directory string
     *
     * Verifies the directory exists, raising an exception if it does not.
     * Normalizes by trimming trailing directory separator characters and
     * appending a standard value (/).
     *
     * @throws InvalidArgumentException When the directory does not exist.
     */
    private function normalizeDirectory(string $directory, string $prefix): string
    {
        if (! is_dir($directory)) {
            throw new InvalidArgumentException(sprintf(
                'The document root for "%s", "%s", does not exist; please check your configuration.',
                empty($prefix) ? "(Default)" : $prefix,
                $directory
            ));
        }
        if ($directory === '/') {
            return '/';
        }
        if ($directory === '\\') {
            return '/';
        }
        if ($directory === '\\\\') {
            return '/';
        }

        return sprintf('%s/', rtrim($directory, '\\/'));
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
                if (stripos($filename, (string) $prefix) === 0) {
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
