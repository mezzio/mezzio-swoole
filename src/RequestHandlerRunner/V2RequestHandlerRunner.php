<?php

declare(strict_types=1);

namespace Mezzio\Swoole\RequestHandlerRunner;

use Laminas\HttpHandlerRunner\RequestHandlerRunnerInterface;

final class V2RequestHandlerRunner implements
    RequestHandlerConstantsInterface,
    RequestHandlerRunnerInterface
{
    use RequestHandlerRunnerTrait;
}
