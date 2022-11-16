<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole;

use Mezzio\Swoole\SwooleStream;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Swoole\Http\Request as SwooleHttpRequest;

use function extension_loaded;
use function strlen;
use function substr;

use const SEEK_CUR;
use const SEEK_END;

class SwooleStreamTest extends TestCase
{
    /**
     * @var string
     */
    public const DEFAULT_CONTENT = 'This is a test!';

    /** @psalm-var SwooleHttpRequest&MockObject */
    private SwooleHttpRequest|MockObject $request;

    private SwooleStream $stream;

    protected function setUp(): void
    {
        if (! extension_loaded('swoole')) {
            $this->markTestSkipped('The Swoole extension is not available');
        }

        $this->request = $this->createMock(SwooleHttpRequest::class);
        $this->request
            ->method('rawContent')
            ->willReturn(self::DEFAULT_CONTENT);

        $this->stream = new SwooleStream($this->request);
    }

    public function testEofWhenOneCharacterLeftCase(): void
    {
        $len = strlen(self::DEFAULT_CONTENT);

        $this->assertEquals('This is a test', $this->stream->read($len - 1));
        $this->assertFalse($this->stream->eof());

        $this->assertEquals('!', $this->stream->read(1));
        $this->assertTrue($this->stream->eof());
    }

    public function testGetContentsWithNoRawContent(): void
    {
        $request = $this->createMock(SwooleHttpRequest::class);
        $request
            ->method('rawContent')
            ->willReturn(false);

        $stream = new SwooleStream($request);

        $this->assertEquals('', $stream->getContents());
    }

    public function testStreamIsAPsr7StreamInterface(): void
    {
        $this->assertInstanceOf(StreamInterface::class, $this->stream);
    }

    public function testGetContentsWhenIndexIsAtStartOfContentReturnsFullContents(): void
    {
        $this->assertEquals(self::DEFAULT_CONTENT, $this->stream->getContents());
    }

    public function testGetContentsReturnsOnlyFromIndexForward(): void
    {
        $index = 10;
        $this->stream->seek($index);
        $this->assertEquals(substr(self::DEFAULT_CONTENT, $index), $this->stream->getContents());
    }

    public function testGetContentsWithEmptyBodyReturnsEmptyString(): void
    {
        $request = $this->createMock(SwooleHttpRequest::class);
        $request
            ->method('rawContent')
            ->willReturn('');
        $this->stream = new SwooleStream($request);

        $this->assertEquals('', $this->stream->getContents());
    }

    public function testToStringReturnsFullContents(): void
    {
        $this->assertEquals(self::DEFAULT_CONTENT, (string) $this->stream);
    }

    public function testToStringReturnsAllContentsEvenWhenIndexIsNotAtStart(): void
    {
        $this->stream->seek(10);
        $this->assertEquals(self::DEFAULT_CONTENT, (string) $this->stream);
    }

    public function testGetSizeReturnsRawContentSize(): void
    {
        $this->assertEquals(
            strlen(self::DEFAULT_CONTENT),
            $this->stream->getSize()
        );
    }

    public function testGetSizeWithEmptyBodyReturnsZero(): void
    {
        $request = $this->createMock(SwooleHttpRequest::class);
        $request
            ->method('rawContent')
            ->willReturn('');
        $this->stream = new SwooleStream($request);

        $this->assertEquals(0, $this->stream->getSize());
    }

    public function testTellIndicatesIndexInString(): void
    {
        for ($i = 0; $i < strlen(self::DEFAULT_CONTENT); ++$i) {
            $this->stream->seek($i);
            $this->assertEquals($i, $this->stream->tell());
        }
    }

    public function testIsReadableReturnsTrue(): void
    {
        $this->assertTrue($this->stream->isReadable());
    }

    public function testReadReturnsStringWithGivenLengthAndResetsIndex(): void
    {
        $result = $this->stream->read(4);
        $this->assertEquals(substr(self::DEFAULT_CONTENT, 0, 4), $result);
        $this->assertEquals(4, $this->stream->tell());
    }

    public function testReadReturnsSubstringFromCurrentIndex(): void
    {
        $this->stream->seek(4);
        $result = $this->stream->read(4);
        $this->assertEquals(substr(self::DEFAULT_CONTENT, 4, 4), $result);
        $this->assertEquals(8, $this->stream->tell());
    }

    public function testIsSeekableReturnsTrue(): void
    {
        $this->assertTrue($this->stream->isSeekable());
    }

    public function testSeekUpdatesIndexPosition(): void
    {
        $this->stream->seek(4);
        $this->assertEquals(4, $this->stream->tell());
        $this->stream->seek(1, SEEK_CUR);
        $this->assertEquals(5, $this->stream->tell());
        $this->stream->seek(-1, SEEK_END);
        $this->assertEquals(strlen(self::DEFAULT_CONTENT) - 1, $this->stream->tell());
    }

    public function testSeekSetRaisesExceptionIfPositionOverflows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Offset cannot be longer than content size');
        $this->stream->seek(strlen(self::DEFAULT_CONTENT));
    }

    public function testSeekCurRaisesExceptionIfPositionOverflows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Offset + current position cannot be longer than content size when using SEEK_CUR'
        );
        $this->stream->seek(strlen(self::DEFAULT_CONTENT), SEEK_CUR);
    }

    public function testSeekEndRaisesExceptionIfPOsitionOverflows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Offset must be a negative number to be under the content size when using SEEK_END'
        );
        $this->stream->seek(1, SEEK_END);
    }

    public function testRewindResetsPositionToZero(): void
    {
        $this->stream->rewind();
        $this->assertEquals(0, $this->stream->tell());
    }

    public function testIsWritableReturnsFalse(): void
    {
        $this->assertFalse($this->stream->isWritable());
    }

    public function testWriteRaisesException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stream is not writable');
        $this->stream->write('Hello!');
    }

    public function testGetMetadataWithNoArgumentsReturnsEmptyArray(): void
    {
        $this->assertEquals([], $this->stream->getMetadata());
    }

    public function testGetMetadataWithStringArgumentReturnsNull(): void
    {
        $this->assertNull($this->stream->getMetadata('foo'));
    }

    public function testDetachReturnsRequestInstance(): void
    {
        $this->assertSame($this->request, $this->stream->detach());
    }

    public function testCloseReturnsNull(): void
    {
        $this->assertNull($this->stream->close());
    }
}
