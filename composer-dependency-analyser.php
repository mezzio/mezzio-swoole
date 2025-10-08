<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;

$config = new Configuration();
return $config
    // Ignore unknown classes for PHP 8.2 compatibility. Remove after dropping PHP 8.2 support.
     ->ignoreUnknownClasses([Override::class])
    ->ignoreUnknownFunctions(["inotify_add_watch", "inotify_init", "inotify_read"]);
