<?php

declare(strict_types=1);

namespace MezzioTest\Swoole\TestAsset;

class ClassWithCallbacks
{
    /**
     * @param array $payload Array of arguments
     * @psalm-param list<mixed> $payload
     */
    public static function staticCallback(...$payload): void
    {
    }

    /**
     * @param array $payload Array of arguments
     * @psalm-param list<mixed> $payload
     */
    public function instanceCallback(...$payload): void
    {
    }
}
