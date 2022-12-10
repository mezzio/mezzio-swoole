<?php // phpcs:disable WebimpressCodingStandard.NamingConventions.ValidVariableName.NotCamelCapsProperty

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\StaticResourceHandler;

use Mezzio\Swoole\Exception\InvalidArgumentException;
use Swoole\Http\Request;

use function array_walk;
use function explode;
use function filemtime;
use function filesize;
use function implode;
use function in_array;
use function md5_file;
use function preg_match;
use function sprintf;

class ETagMiddleware implements MiddlewareInterface
{
    use ValidateRegexTrait;

    /**
     * ETag validation type
     *
     * @var string
     */
    public const ETAG_VALIDATION_STRONG = 'strong';

    /**
     * @var string
     */
    public const ETAG_VALIDATION_WEAK = 'weak';

    /** @var string[] */
    private array $allowedETagValidationTypes = [
        self::ETAG_VALIDATION_STRONG,
        self::ETAG_VALIDATION_WEAK,
    ];

    /**
     * @var string[] Array of regexp; if a path matches a regexp, an ETag will
     *     be emitted for the static file resource.
     */
    private array $etagDirectives = [];

    /**
     * ETag validation type, 'weak' means Weak Validation, 'strong' means Strong Validation,
     * other value will not response ETag header.
     */
    private string $etagValidationType;

    public function __construct(array $etagDirectives = [], string $etagValidationType = self::ETAG_VALIDATION_WEAK)
    {
        $this->validateRegexList($etagDirectives, 'ETag');
        if (! in_array($etagValidationType, $this->allowedETagValidationTypes, true)) {
            throw new InvalidArgumentException(sprintf(
                'ETag validation type must be one of [%s]; received "%s"',
                implode(', ', $this->allowedETagValidationTypes),
                $etagValidationType
            ));
        }

        $this->etagDirectives     = $etagDirectives;
        $this->etagValidationType = $etagValidationType;
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(Request $request, string $filename, callable $next): StaticResourceResponse
    {
        $response = $next($request, $filename);

        if (! $this->getETagFlagForPath($request->server['request_uri'])) {
            return $response;
        }

        return $this->prepareETag($request, $filename, $response);
    }

    private function getETagFlagForPath(string $path): bool
    {
        foreach ($this->etagDirectives as $regexp) {
            if (preg_match($regexp, $path)) {
                return true;
            }
        }

        return false;
    }

    private function prepareETag(
        Request $request,
        string $filename,
        StaticResourceResponse $response
    ): StaticResourceResponse {
        $lastModified = filemtime($filename) ?: 0;
        switch ($this->etagValidationType) {
            case self::ETAG_VALIDATION_WEAK:
                $filesize = filesize($filename) ?: 0;
                if (! $lastModified || ! $filesize) {
                    return $response;
                }

                $etag = sprintf('W/"%x-%x"', $lastModified, $filesize);
                break;
            case self::ETAG_VALIDATION_STRONG:
                $etag = md5_file($filename);
                break;
            default:
                return $response;
        }

        $response->addHeader('ETag', $etag);

        // Determine if ETag the client expects matches calculated ETag
        $ifMatch     = $request->header['if-match'] ?? '';
        $ifNoneMatch = $request->header['if-none-match'] ?? '';
        $clientEtags = explode(',', $ifMatch ?: $ifNoneMatch);
        array_walk($clientEtags, 'trim');

        if (in_array($etag, $clientEtags, true)) {
            $response->setStatus(304);
            $response->disableContent();
        }

        return $response;
    }
}
