<?php

declare(strict_types=1);

namespace MezzioTest\Swoole\TestAsset;

class CallableObject
{
    /**
     * @param array $params Array of arguments
     * @return array Array of arguments
     * @psalm-param list<mixed> $params
     * @psalm-return list<mixed>
     */
    public function __invoke(...$params): array
    {
        return $params;
    }
}
