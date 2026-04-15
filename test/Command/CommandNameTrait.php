<?php

declare(strict_types=1);

namespace MezzioTest\Swoole\Command;

use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;

use function assert;
use function class_exists;

trait CommandNameTrait
{
    /**
     * @param class-string $commandClass
     */
    public static function commandName(string $commandClass): ?string
    {
        assert(class_exists($commandClass));
        if ($attribute = (new ReflectionClass($commandClass))->getAttributes(AsCommand::class)) {
            return $attribute[0]->newInstance()->name;
        }

        return null;
    }
}
