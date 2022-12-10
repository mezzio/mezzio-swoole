<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole;

use Mezzio\Swoole\StaticResourceHandler\StaticResourceResponse;
use PHPUnit\Framework\Assert;
use ReflectionProperty;

use function array_keys;
use function implode;
use function sprintf;

trait AssertResponseTrait
{
    public function assertStatus(int $expected, StaticResourceResponse $response, string $message = ''): void
    {
        $r = new ReflectionProperty($response, 'status');
        $r->setAccessible(true);

        $actual = $r->getValue($response);

        $message = $message ?: sprintf(
            'Failed asserting that static resource response status is "%d"; actual is "%d"',
            $expected,
            $actual
        );

        Assert::assertSame($expected, $actual, $message);
    }

    public function assertHeadersEmpty(StaticResourceResponse $response, string $message = ''): void
    {
        $r = new ReflectionProperty($response, 'headers');
        $r->setAccessible(true);

        $headers = $r->getValue($response);

        $message = $message ?: sprintf(
            'Failed asserting that static resource response has no headers; found [%s]',
            implode(', ', array_keys($headers))
        );

        Assert::assertEmpty($headers, $message);
    }

    public function assertHeaderExists(string $name, StaticResourceResponse $response, string $message = ''): void
    {
        $message = $message ?: sprintf(
            'Failed asserting that static resource response contains header by name "%s"',
            $name
        );

        $r = new ReflectionProperty($response, 'headers');
        $r->setAccessible(true);

        $headers = $r->getValue($response);

        Assert::assertArrayHasKey($name, $headers, $message);
    }

    public function assertHeaderNotExists(string $name, StaticResourceResponse $response, string $message = ''): void
    {
        $message = $message ?: sprintf(
            'Failed asserting that static resource response does not contain header by name "%s"',
            $name
        );

        $r = new ReflectionProperty($response, 'headers');
        $r->setAccessible(true);

        $headers = $r->getValue($response);

        Assert::assertArrayNotHasKey($name, $headers, $message);
    }

    public function assertHeaderSame(
        string $expected,
        string $header,
        StaticResourceResponse $response,
        string $message = ''
    ): void {
        $this->assertHeaderExists($header, $response);

        $r = new ReflectionProperty($response, 'headers');
        $r->setAccessible(true);

        $headers = $r->getValue($response);
        $value   = $headers[$header];

        $message = $message ?: sprintf(
            'Failed asserting that static resource response header "%s" (value "%s") is "%s"',
            $header,
            $value,
            $expected
        );

        Assert::assertSame($expected, $value, $message);
    }

    public function assertHeaderRegexp(
        string $regexp,
        string $header,
        StaticResourceResponse $response,
        string $message = ''
    ): void {
        $this->assertHeaderExists($header, $response);

        $r = new ReflectionProperty($response, 'headers');
        $r->setAccessible(true);

        $headers = $r->getValue($response);
        $value   = $headers[$header];

        $message = $message ?: sprintf(
            'Failed asserting that static resource response header "%s" (value "%s") matches "%s"',
            $header,
            $value,
            $regexp
        );

        Assert::assertMatchesRegularExpression($regexp, $value, $message);
    }

    public function assertShouldSendContent(StaticResourceResponse $response, string $message = ''): void
    {
        $message = $message ?: 'Failed asserting that the static resource response should send content';

        $r = new ReflectionProperty($response, 'sendContent');
        $r->setAccessible(true);

        $value = $r->getValue($response);
        Assert::assertTrue($value, $message);
    }

    public function assertShouldNotSendContent(StaticResourceResponse $response, string $message = ''): void
    {
        $message = $message ?: 'Failed asserting that the static resource response should not send content';

        $r = new ReflectionProperty($response, 'sendContent');
        $r->setAccessible(true);

        $value = $r->getValue($response);
        Assert::assertFalse($value, $message);
    }
}
