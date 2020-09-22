<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole\StaticResourceHandler;

/**
 * Interface to implement a repository for storing the association
 * between the start of a URI (prefix) and directory
 */
interface FileLocationRepositoryInterface
{
    public function addMappedDocumentRoot(string $prefix, string $directory): void;

    public function listMappedDocumentRoots(): array;

    public function findFile(string $filename): ?string;
}
