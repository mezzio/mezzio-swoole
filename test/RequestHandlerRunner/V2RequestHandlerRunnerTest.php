<?php

declare(strict_types=1);

namespace MezzioTest\Swoole\RequestHandlerRunner;

use Laminas\HttpHandlerRunner\RequestHandlerRunnerInterface;
use Mezzio\Swoole\RequestHandlerRunner\V2RequestHandlerRunner;

class V2RequestHandlerRunnerTest extends AbstractRequestHandlerRunnerTest
{
    public function getRequestHandlerRunnerClass(): string
    {
        return V2RequestHandlerRunner::class;
    }

    public function determineWhetherOrNotToSkipRequestHandlerRunnerTests(): void
    {
        if (! interface_exists(RequestHandlerRunnerInterface::class)) {
            $this->markTestSkipped(
                'Skipping tests for laminas-httphandlerrunner v2 variant of SwooleRequestHandlerRunner'
            );
        }
    }
}
