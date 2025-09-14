<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\TestAsset;

final class ClassWithCallbacks
{
    public static function staticCallback(): void
    {
    }

    public function instanceCallback(): void
    {
    }
}
