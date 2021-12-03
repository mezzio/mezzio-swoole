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
            '/(?P<token>%[aAbBcCdDeFgGhHIjklmMpPrRsSTuUVwWxXyYzZ])/',
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
        return function (array $matches) use ($requestTime): string {
            Assert::keyExists($matches, 'token');
            switch (true) {
                case $matches['token'] === '%a':
                    return 'eee';
                case $matches['token'] === '%A':
                    return 'eeee';
                case $matches['token'] === '%b':
                    return 'MMM';
                case $matches['token'] === '%B':
                    return 'MMMM';
                case $matches['token'] === '%C':
                    return 'yy';
                case $matches['token'] === '%d':
                    return 'dd';
                case $matches['token'] === '%D':
                    return 'MM/dd/yy';
                case $matches['token'] === '%e':
                    return ' d';
                case $matches['token'] === '%F':
                    return 'y-MM-dd';
                case $matches['token'] === '%g':
                    return 'yy';
                case $matches['token'] === '%G':
                    return 'y';
                case $matches['token'] === '%h':
                    return 'MMM';
                case $matches['token'] === '%H':
                    return 'HH';
                case $matches['token'] === '%I':
                    return 'KK';
                case $matches['token'] === '%j':
                    return 'D';
                case $matches['token'] === '%k':
                    return ' H';
                case $matches['token'] === '%l':
                    return ' h';
                case $matches['token'] === '%m':
                    return 'MM';
                case $matches['token'] === '%M':
                    return 'mm';
                case $matches['token'] === '%p':
                    return 'a';
                case $matches['token'] === '%P':
                    return 'a';
                case $matches['token'] === '%r':
                    return ' h:mm:ss a';
                case $matches['token'] === '%R':
                    return 'HH:mm';
                case $matches['token'] === '%S':
                    return 'ss';
                case $matches['token'] === '%s':
                    return (string) $requestTime->getTimestamp();
                case $matches['token'] === '%T':
                    return 'HH:mm:ss';
                case $matches['token'] === '%u':
                    return 'e';
                case $matches['token'] === '%U':
                    return 'ww';
                case $matches['token'] === '%w':
                    return 'c';
                case $matches['token'] === '%W':
                    return 'ww';
                case $matches['token'] === '%V':
                    return 'ww';
                case $matches['token'] === '%y':
                    return 'yy';
                case $matches['token'] === '%Y':
                    return 'y';
                case $matches['token'] === '%z':
                    return 'xx';
                case $matches['token'] === '%Z':
                    return 'z';
                default:
                    throw new RuntimeException(sprintf(
                        'The request time format token "%s" is unsupported; please use ICU Date/Time format codes',
                        $matches['token']
                    ));
            }
        };
    }
}
