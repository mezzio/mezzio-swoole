<?php

declare(strict_types=1);

namespace MezzioTest\Swoole\Log;

use Mezzio\Swoole\Log\StrftimeToICUFormatMap;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class StrftimeToICUFormatMapTest extends TestCase
{
    public function testPatternsUsedInAccessLogFormatter(): void
    {
        $this->assertSame('dd/MMM/y:HH:mm:ss xx', StrftimeToICUFormatMap::mapStrftimeToICU('%d/%b/%Y:%H:%M:%S %z'));
    }

    public function testDoesNotReplaceICUFormats(): void
    {
        $this->assertSame('dd/MMM/y:HH:mm:ss xx', StrftimeToICUFormatMap::mapStrftimeToICU('dd/MMM/y:HH:mm:ss xx'));
    }

    /** @psalm-return array<string, array{0: non-empty-string}> */
    public function unsupportedFormats(): array
    {
        return [
            '%c' => ['%c'],
            '%x' => ['%x'],
            '%X' => ['%X'],
        ];
    }

    /**
     * @dataProvider unsupportedFormats
     * @psalm-param non-empty-string $format
     */
    public function testRaisesExceptionForUnsupportedFormats(string $format): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('unsupported');
        StrftimeToICUFormatMap::mapStrftimeToICU($format);
    }
}
