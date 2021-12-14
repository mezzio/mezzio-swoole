<?php

declare(strict_types=1);

namespace Mezzio\Swoole;

use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Mezzio\Swoole\RequestHandlerRunner\RequestHandlerConstantsInterface;
use Mezzio\Swoole\RequestHandlerRunner\RequestHandlerRunnerTrait;

/**
 * Starts a Swoole web server that handles incoming requests.
 *
 * Registers callbacks on each server event that marshal a typed event with the
 * arguments provided to the callback, and then dispatches the event using a
 * PSR-14 dispatcher.
 */
class SwooleRequestHandlerRunner extends RequestHandlerRunner implements RequestHandlerConstantsInterface
{
    use RequestHandlerRunnerTrait;
}
