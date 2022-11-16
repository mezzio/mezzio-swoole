<?php

declare(strict_types=1);

namespace MezzioTest\Swoole;

use Mezzio\Swoole\Exception\ExtensionNotLoadedException;

use function extension_loaded;

require __DIR__ . '/../vendor/autoload.php';
if (extension_loaded('swoole')) {
    return;
}
if (extension_loaded('openswoole')) {
    return;
}
throw new ExtensionNotLoadedException(
    'One of either the swoole or openswoole extensions must be loaded to use mezzio/mezzio-swoole'
);
