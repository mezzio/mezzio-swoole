<?php // phpcs:disable WebimpressCodingStandard.NamingConventions.ValidVariableName.NotCamelCaps

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\StaticResourceHandler;

use Mezzio\Swoole\Exception\InvalidArgumentException;
use Mezzio\Swoole\StaticResourceHandler\ETagMiddleware;
use Mezzio\Swoole\StaticResourceHandler\StaticResourceResponse;
use MezzioTest\Swoole\AssertResponseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Swoole\Http\Request;

use function array_unshift;
use function basename;
use function filemtime;
use function filesize;
use function md5_file;
use function sprintf;

class ETagMiddlewareTest extends TestCase
{
    use AssertResponseTrait;

    /** @var callable */
    private $next;

    /**
     * @var Request|MockObject
     * @psalm-var MockObject&Request
     */
    private $request;

    protected function setUp(): void
    {
        $this->next    = static function (Request $request, string $filename): StaticResourceResponse {
            return new StaticResourceResponse();
        };
        $this->request = $this->createMock(Request::class);
    }

    public function testConstructorRaisesExceptionForInvalidRegexInDirectiveList(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('regex');
        new ETagMiddleware(['not-a-valid-regex']);
    }

    public function testConstructorRaisesExceptionForInvalidETagValidationType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ETag validation type');
        new ETagMiddleware([], 'invalid-etag-validation-type');
    }

    public function testMiddlewareDoesNothingWhenRequestPathDoesNotMatchADirective(): void
    {
        $this->request->server = [
            'request_uri' => '/any/path/at/all',
        ];

        $middleware = new ETagMiddleware([]);

        $response = $middleware($this->request, '/any/path/at/all', $this->next);

        $this->assertStatus(200, $response);
        $this->assertHeaderNotExists('ETag', $response);
        $this->assertShouldSendContent($response);
    }

    /**
     * @psalm-return array<array-key, array{
     *     0: string,
     *     1: string,
     *     2: string,
     *     3: string,
     * }>
     */
    public function expectedEtagProvider(): array
    {
        $filename     = __DIR__ . '/../TestAsset/image.png';
        $lastModified = filemtime($filename);
        $filesize     = filesize($filename);

        $weakETag   = sprintf('W/"%x-%x"', $lastModified, $filesize);
        $strongETag = md5_file($filename);

        return [
            'weak'   => [ETagMiddleware::ETAG_VALIDATION_WEAK, '/\.png$/', $filename, $weakETag],
            'strong' => [ETagMiddleware::ETAG_VALIDATION_STRONG, '/\.png$/', $filename, $strongETag],
        ];
    }

    /**
     * @dataProvider expectedEtagProvider
     */
    public function testMiddlewareProvidesETagAccordingToValidationStrengthWhenFileMatchesDirective(
        string $validationType,
        string $regex,
        string $filename,
        string $expectedETag
    ): void {
        $this->request->server = [
            'request_uri' => '/images/' . basename($filename),
        ];

        $middleware = new ETagMiddleware([$regex], $validationType);

        $response = $middleware($this->request, $filename, $this->next);

        $this->assertStatus(200, $response);
        $this->assertHeaderExists('ETag', $response);
        $this->assertHeaderSame($expectedETag, 'ETag', $response);
        $this->assertShouldSendContent($response);
    }

    public function clientMatchHeaders(): iterable
    {
        $clientHeaders = ['if-match', 'if-none-match'];

        foreach ($clientHeaders as $header) {
            foreach ($this->expectedEtagProvider() as $case => $arguments) {
                $name = sprintf('%s - %s', $header, $case);
                array_unshift($arguments, $header);
                yield $name => $arguments;
            }
        }
    }

    /**
     * @dataProvider clientMatchHeaders
     */
    public function testMiddlewareDisablesResponseContentWhenETagMatchesClientHeader(
        string $clientHeader,
        string $validationType,
        string $regex,
        string $filename,
        string $expectedETag
    ): void {
        $this->request->server = [
            'request_uri' => '/images/' . basename($filename),
        ];
        $this->request->header = [
            $clientHeader => $expectedETag,
        ];

        $middleware = new ETagMiddleware([$regex], $validationType);

        $response = $middleware($this->request, $filename, $this->next);

        $this->assertStatus(304, $response);
        $this->assertHeaderExists('ETag', $response);
        $this->assertHeaderSame($expectedETag, 'ETag', $response);
        $this->assertShouldNotSendContent($response);
    }
}
