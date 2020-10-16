<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\TestAsset;

class CallableObject
{
    /**
     * @param array $params Array of arguments
     * @return array Array of arguments
     * @psalm-param list<mixed> $params
     * @psalm-return list<mixed>
     */
    public function __invoke(...$params): array
    {
        return $params;
    }
}
