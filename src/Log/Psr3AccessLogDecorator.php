<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Log;

use Mezzio\Swoole\StaticResourceHandler\StaticResourceResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Swoole\Http\Request;

class Psr3AccessLogDecorator implements AccessLogInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private AccessLogFormatterInterface $formatter,
        /**
         * Whether or not to look up remote host names when preparing the access
         * log message
         */
        private bool $useHostnameLookups = false
    ) {
    }

    public function logAccessForStaticResource(Request $request, StaticResourceResponse $response): void
    {
        $message = $this->formatter->format(
            AccessLogDataMap::createWithStaticResource($request, $response, $this->useHostnameLookups)
        );
        $response->getStatus() >= 400
            ? $this->logger->error($message)
            : $this->logger->info($message);
    }

    public function logAccessForPsr7Resource(Request $request, ResponseInterface $response): void
    {
        $message = $this->formatter->format(
            AccessLogDataMap::createWithPsrResponse($request, $response, $this->useHostnameLookups)
        );
        $response->getStatusCode() >= 400
            ? $this->logger->error($message)
            : $this->logger->info($message);
    }

    // phpcs:disable WebimpressCodingStandard.Functions.Param.MissingSpecification
    // phpcs:disable WebimpressCodingStandard.Functions.ReturnType.ReturnValue

    /**
     * {@inheritDoc}
     */
    public function emergency($message, array $context = []): void
    {
        $this->logger->emergency($message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function alert($message, array $context = []): void
    {
        $this->logger->alert($message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function critical($message, array $context = []): void
    {
        $this->logger->critical($message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function error($message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function warning($message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function notice($message, array $context = []): void
    {
        $this->logger->notice($message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function info($message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function debug($message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function log($level, $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }
}
