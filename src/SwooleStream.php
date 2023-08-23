<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Stringable;
use Swoole\Http\Request as SwooleHttpRequest;

use function strlen;
use function substr;

use const SEEK_CUR;
use const SEEK_END;
use const SEEK_SET;

final class SwooleStream implements StreamInterface, Stringable
{
    /**
     * Memoized body content, as pulled via SwooleHttpRequest::rawContent().
     */
    private ?string $body = null;
    /**
     * Length of the request body content.
     */
    private ?int $bodySize = null;
    /**
     * Index to which we have seek'd or read within the request body.
     */
    private int $index = 0;
    public function __construct(
        /**
         * Swoole request containing the body contents.
         */
        private SwooleHttpRequest $request
    ) {
    }

    public function getContents(): string
    {
        // If we're at the end of the string, return an empty string.
        if ($this->eof()) {
            return '';
        }

        $size = $this->getSize();
        // If we have not content, return an empty string
        if ($size === 0) {
            return '';
        }

        // Memoize index so we can use it to get a substring later,
        // if required.
        $index = $this->index;

        // Set the internal index to the end of the string
        $this->index = $size;

        if ($index && null !== $this->body) {
            // Per PSR-7 spec, if we have seeked or read to a given position in
            // the string, we should only return the contents from that position
            // forward.
            $remaining = substr($this->body, $index);

            return $remaining ?: '';
        }

        // If we're at the start of the content, return all of it.
        return (string) $this->body;
    }

    public function __toString(): string
    {
        if ($this->body === null) {
            $this->initRawContent();
        }
        return (string) $this->body;
    }

    public function getSize(): ?int
    {
        if (null === $this->bodySize) {
            if ($this->body === null) {
                $this->initRawContent();
            }
            $this->bodySize = strlen($this->body);
        }

        return $this->bodySize;
    }

    public function tell(): int
    {
        return $this->index;
    }

    public function eof(): bool
    {
        return $this->index >= $this->getSize();
    }

    /**
     * @return bool Always returns true.
     */
    public function isReadable(): bool
    {
        return true;
    }

    public function read(int $length): string
    {
        if ($this->body === null) {
            $this->initRawContent();
        }
        $result = substr($this->body, $this->index, $length);

        // Reset index based on legnth; should not be > EOF position.
        $size        = $this->getSize();
        $this->index = $this->index + $length >= $size
            ? $size
            : $this->index + $length;

        return $result;
    }

    /**
     * @return bool Always returns true.
     */
    public function isSeekable(): bool
    {
        return true;
    }

    /**
     * @psalm-return void
     */
    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $size = $this->getSize();
        switch ($whence) {
            case SEEK_SET:
                if ($offset >= $size) {
                    throw new RuntimeException(
                        'Offset cannot be longer than content size'
                    );
                }

                $this->index = $offset;
                break;
            case SEEK_CUR:
                if ($offset + $this->index >= $size) {
                    throw new RuntimeException(
                        'Offset + current position cannot be longer than content size when using SEEK_CUR'
                    );
                }

                $this->index += $offset;
                break;
            case SEEK_END:
                if ($offset + $size >= $size) {
                    throw new RuntimeException(
                        'Offset must be a negative number to be under the content size when using SEEK_END'
                    );
                }

                $this->index = $size + $offset;
                break;
            default:
                throw new InvalidArgumentException(
                    'Invalid $whence argument provided; must be one of SEEK_CUR,SEEK_END, or SEEK_SET'
                );
        }
    }

    /**
     * @psalm-return void
     */
    public function rewind(): void
    {
        $this->index = 0;
    }

    /**
     * @return bool Always returns false.
     */
    public function isWritable(): bool
    {
        return false;
    }

    // phpcs:disable Squiz.Commenting.FunctionComment.InvalidNoReturn
    /**
     * @throws RuntimeException Always throws, as not writable.
     */
    public function write(string $string): int
    {
        throw new RuntimeException('Stream is not writable');
    }

    // phpcs:enable
    /**
     * @param string $key
     * @return null|array
     */
    public function getMetadata($key = null): ?array
    {
        return $key ? null : [];
    }

    public function detach(): SwooleHttpRequest
    {
        return $this->request;
    }

    public function close(): void
    {
    }

    // phpcs:enable
    /**
     * Memoize the request raw content in the $body property, if not already done.
     */
    private function initRawContent(): void
    {
        if ($this->body) {
            return;
        }

        $this->body = $this->request->rawContent() ?: '';
    }
}
