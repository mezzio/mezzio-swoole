<?php

declare(strict_types=1);

namespace Mezzio\Swoole\Log;

use DateTimeInterface;
use RuntimeException;
use Webmozart\Assert\Assert;

use function preg_replace_callback;
use function sprintf;

/**
 * Translate a strftime format to an ICU date/time format.
 *
 * This will translate all but %X, %x, and %c, for which there are no ICU
 * equivalents.
 *
 * @internal
 *
 * @see https://www.php.net/strftime for PHP strftime format strings
 * @see https://unicode-org.github.io/icu/userguide/format_parse/datetime/ for ICU Date/Time format strings
 */
final class StrftimeToICUFormatMap
{
    public static function mapStrftimeToICU(string $format, DateTimeInterface $requestTime): string
    {
        return preg_replace_callback(
            '#(?P<token>%[aAbBcCdDeFgGhHIjklmMpPrRsSTuUVwWxXyYzZ])#',
            self::generateMapCallback($requestTime),
            $format
        );
    }

    /**
     * @psalm-return callable(array<array-key, string>):string
     */
    private static function generateMapCallback(DateTimeInterface $requestTime): callable
    {
        /** @psalm-param array<array-key, string> */
        return static function (array $matches) use ($requestTime): string {
            Assert::keyExists($matches, 'token');
            return match (true) {
                $matches['token'] === '%a' => 'eee',
                $matches['token'] === '%A' => 'eeee',
                $matches['token'] === '%b' => 'MMM',
                $matches['token'] === '%B' => 'MMMM',
                $matches['token'] === '%C' => 'yy',
                $matches['token'] === '%d' => 'dd',
                $matches['token'] === '%D' => 'MM/dd/yy',
                $matches['token'] === '%e' => ' d',
                $matches['token'] === '%F' => 'y-MM-dd',
                $matches['token'] === '%g' => 'yy',
                $matches['token'] === '%G' => 'y',
                $matches['token'] === '%h' => 'MMM',
                $matches['token'] === '%H' => 'HH',
                $matches['token'] === '%I' => 'KK',
                $matches['token'] === '%j' => 'D',
                $matches['token'] === '%k' => ' H',
                $matches['token'] === '%l' => ' h',
                $matches['token'] === '%m' => 'MM',
                $matches['token'] === '%M' => 'mm',
                $matches['token'] === '%p' => 'a',
                $matches['token'] === '%P' => 'a',
                $matches['token'] === '%r' => ' h:mm:ss a',
                $matches['token'] === '%R' => 'HH:mm',
                $matches['token'] === '%S' => 'ss',
                $matches['token'] === '%s' => (string) $requestTime->getTimestamp(),
                $matches['token'] === '%T' => 'HH:mm:ss',
                $matches['token'] === '%u' => 'e',
                $matches['token'] === '%U' => 'ww',
                $matches['token'] === '%w' => 'c',
                $matches['token'] === '%W' => 'ww',
                $matches['token'] === '%V' => 'ww',
                $matches['token'] === '%y' => 'yy',
                $matches['token'] === '%Y' => 'y',
                $matches['token'] === '%z' => 'xx',
                $matches['token'] === '%Z' => 'z',
                default => throw new RuntimeException(sprintf(
                    'The request time format token "%s" is unsupported; please use ICU Date/Time format codes',
                    $matches['token']
                )),
            };
        };
    }
}
