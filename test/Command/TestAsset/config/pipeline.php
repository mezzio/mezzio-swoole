<?php

declare(strict_types=1);

namespace MezzioTest\Swoole\Command\TestAsset\config;

use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Psr\Container\ContainerInterface;

return static function (Application $app, MiddlewareFactory $factory, ContainerInterface $container) {
};
