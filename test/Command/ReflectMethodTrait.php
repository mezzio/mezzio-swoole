<?php

declare(strict_types=1);

namespace MezzioTest\Swoole\Command;

use ReflectionMethod;
use Symfony\Component\Console\Command\Command;

trait ReflectMethodTrait
{
    public function reflectMethod(Command $command, string $method): ReflectionMethod
    {
        return new ReflectionMethod($command, $method);
    }
}
