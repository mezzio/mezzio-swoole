<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

$config = new Configuration();
return $config
    // Ignore unknown classes for PHP 8.2 compatibility. Remove after dropping PHP 8.2 support.
    ->ignoreUnknownClasses([Override::class])
    ->ignoreUnknownFunctions(["inotify_add_watch", "inotify_init", "inotify_read"])
    //meta-package with no code; only declares requirement for PSR-7 implementation
    ->ignoreErrorsOnPackage('psr/http-message-implementation', [ErrorType::UNUSED_DEPENDENCY]);
