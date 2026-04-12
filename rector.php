<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictSetUpRector;

return RectorConfig::configure()
    ->withPhpSets(php82: true)
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/test',
    ])
    ->withPreparedSets(
        typeDeclarations: true,
    )
    ->withSkip([
        __DIR__ . '/test/TestAsset',
        //  $this->httpServer->on('managerstart', [$this, 'onManagerStart']);
        // vs
        // $this->httpServer->on('managerstart', $this->onManagerStart(...)
        // is not the same
        ArrayToFirstClassCallableRector::class => [
            __DIR__ . '/src/SwooleRequestHandlerRunner.php',
            __DIR__ . '/test/SwooleRequestHandlerRunnerTest.php',
            __DIR__ . '/test/Task/TaskTest.php',
        ],
        // Rector try to set callable as Closure. These are not the same — Closure is a subtype of callable.
        TypedPropertyFromStrictSetUpRector::class => [
            __DIR__ . '/test/StaticResourceHandler/LastModifiedMiddlewareTest.php',
            __DIR__ . '/test/StaticResourceHandler/HeadMiddlewareTest.php',
            __DIR__ . '/test/StaticResourceHandler/GzipMiddlewareTest.php',
            __DIR__ . '/test/StaticResourceHandler/ETagMiddlewareTest.php',
        ],
    ]);
