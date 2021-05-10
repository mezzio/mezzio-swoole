<?php

declare(strict_types=1);

namespace Mezzio\Swoole\Log;

interface AccessLogFormatterInterface
{
    public function format(AccessLogDataMap $map): string;
}
