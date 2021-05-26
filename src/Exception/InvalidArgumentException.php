<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Exception;

use InvalidArgumentException as BaseException;

class InvalidArgumentException extends BaseException implements ExceptionInterface
{
}
