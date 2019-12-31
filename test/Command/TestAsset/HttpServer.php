<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Command\TestAsset;

/**
 * Dummy interface defining the HTTP server.
 *
 * For purposes of testing, we only need to pull the server from the container
 * and then call its `set()` method. Unfortunately, Swoole\Http\Server is not
 * mockable (for some reason, mocks of the class do not end up with the same
 * typehints for arguments as the actual class, leading to errors), we can
 * leverage the fact that the container doesn't care what instance is returned,
 * and the code does not verify the type.
 */
interface HttpServer
{
    public function set(array $options) : void;
}
