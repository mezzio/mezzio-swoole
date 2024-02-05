<?php

declare(strict_types=1);

use OpenSwoole\Util;

// @see https://github.com/mezzio/mezzio-swoole/issues/110#issuecomment-1500174967
// Override the swoole_set_process_name function
if (version_compare((string)phpversion('openswoole'), '22.0.0', '>=')) {
    function swoole_set_process_name(string $process_name): void
    {
        Util::setProcessName($process_name);
    }
}
