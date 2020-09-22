<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Command;

use ReflectionMethod;
use Symfony\Component\Console\Command\Command;

trait ReflectMethodTrait
{
    public function reflectMethod(Command $command, string $method): ReflectionMethod
    {
        $r = new ReflectionMethod($command, $method);
        $r->setAccessible(true);
        return $r;
    }
}
