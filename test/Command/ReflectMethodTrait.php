<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
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
