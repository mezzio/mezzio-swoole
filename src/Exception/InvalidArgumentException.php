<?php

declare(strict_types=1);

namespace Mezzio\Swoole\Exception;

use InvalidArgumentException as BaseException;

class InvalidArgumentException extends BaseException implements ExceptionInterface
{
}
