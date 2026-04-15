<?php

declare(strict_types=1);

namespace MezzioTest\Swoole\Command;

use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;

trait CommandNameTrait
{
    /**
     * @param class-string $commandClass
     */
    public function commandName(string $commandClass): ?string
    {
        if ($attribute = (new ReflectionClass($commandClass))->getAttributes(AsCommand::class)) {
            return $attribute[0]->newInstance()->name;
        }

        return null;
    }
}
