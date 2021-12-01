<?php

declare(strict_types=1);

namespace MezzioTest\Swoole;

use DateTimeImmutable;
use DateTimeZone;
use IntlDateFormatter;

trait FormatTimestampTrait
{
    public function formatTimestamp(int $timestamp): string
    {
        $dateTime = new DateTimeImmutable('@' . $timestamp, new DateTimeZone('GMT'));
        return IntlDateFormatter::formatObject(
            $dateTime,
            'EEEE dd-MMM-yy HH:mm:ss z'
        );
    }
}
