<?php

declare(strict_types=1);

namespace Mezzio\Swoole\Exception;

use RuntimeException as BaseException;

class RuntimeException extends BaseException implements ExceptionInterface
{
}
