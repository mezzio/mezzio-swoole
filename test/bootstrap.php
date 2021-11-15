<?php

use Mezzio\Swoole\Exception\ExtensionNotLoadedException;

require __DIR__ . '/../vendor/autoload.php';

if (! extension_loaded('swoole') && ! extension_loaded('openswoole')) {
    throw new ExtensionNotLoadedException(
        'One of either the swoole or openswoole extensions must be loaded to use mezzio/mezzio-swoole'
    );
}
