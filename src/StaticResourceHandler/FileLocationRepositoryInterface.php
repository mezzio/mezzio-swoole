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
    function addMappedDocumentRoot(string $prefix, string $directory): void;
    function listMappedDocumentRoots(): array;
    function findFile(string $filename): ?string;
}