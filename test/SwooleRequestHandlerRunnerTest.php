<?php

declare(strict_types=1);

namespace MezzioTest\Swoole;

use Laminas\HttpHandlerRunner\RequestHandlerRunnerInterface;
use Mezzio\Swoole\SwooleRequestHandlerRunner;

class SwooleRequestHandlerRunnerTest extends RequestHandlerRunner\AbstractRequestHandlerRunnerTest
{
    public function getRequestHandlerRunnerClass(): string
    {
        return SwooleRequestHandlerRunner::class;
    }

    public function determineWhetherOrNotToSkipRequestHandlerRunnerTests(): void
    {
        if (interface_exists(RequestHandlerRunnerInterface::class)) {
            $this->markTestSkipped(
                'Skipping tests for laminas-httphandlerrunner v1 variant of SwooleRequestHandlerRunner'
            );
        }
    }
}
