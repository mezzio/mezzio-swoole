<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Log;

interface AccessLogFormatterInterface
{
    public function format(AccessLogDataMap $map): string;
}
