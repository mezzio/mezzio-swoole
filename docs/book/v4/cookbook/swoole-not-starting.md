# Swoole-based server always returns home page

## The problem

You have started your Swoole-based web-server using `./vendor/bin/laminas mezzio:swoole:start`, but every request is returned with a status 200 response and the contents of the home page.

## The solution

This is generally caused by having the mezzio-swoole configuration provider too early in your application configuration, which then causes the default Mezzio configuration to overwrite the mezzio-swoole application runner.

As an example, if the definition of the `ConfigAggregator` in your `config/config.php` file  looks something like this:

```php
$aggregator = new ConfigAggregator([
    \Mezzio\Swoole\ConfigProvider::class,
    \Mezzio\Plates\ConfigProvider::class,
    \Mezzio\Helper\ConfigProvider::class,
    \Mezzio\Router\FastRouteRouter\ConfigProvider::class,
    \Laminas\HttpHandlerRunner\ConfigProvider::class,
    // Include cache configuration
    new \Laminas\ConfigAggregator\ArrayProvider($cacheConfig),
    \Mezzio\Helper\ConfigProvider::class,
    \Mezzio\ConfigProvider::class,
    \Mezzio\Router\ConfigProvider::class,
    \Laminas\Diactoros\ConfigProvider::class,
    // Default App module config
    \App\ConfigProvider::class,
    // Load application config in a pre-defined order in such a way that local settings
    // overwrite global settings. (Loaded as first to last):
    //   - `global.php`
    //   - `*.global.php`
    //   - `local.php`
    //   - `*.local.php`
    new \Laminas\ConfigAggregator\PhpFileProvider(realpath(__DIR__) . '/autoload/{{,*.}global,{,*.}local}.php'),
    // Load development config if it exists
    new \Laminas\ConfigAggregator\PhpFileProvider(realpath(__DIR__) . '/development.config.php'),
], $cacheConfig['config_cache_path']);
```

Note the position of the `Mezzio\Swoole\ConfigProvider::class` entry at the top.
This can happen if you have previously removed the original entry from the `config/config.php` as provided by the skeleton project, or if you hand-crafted your `config/config.php` file`, and then later used `composer require mezzio/mezzio-swoole`, as the [component installer](https://docs.laminas.dev/laminas-component-installer/) injects at the start of the aggregator definition.

The mezzio-swoole package provides a custom implementation of an [HTTP Handler Runner](https://docs.laminas.dev/laminas-httphandlerrunner/).
Because it defines the _same service name_ as the one provided by laminas/laminas-httphandlerrunner, the mezzio-swoole `ConfigProvider` must appear _later_ during aggregation to ensure it takes precedence:

```php
$aggregator = new ConfigAggregator([
    \Mezzio\Plates\ConfigProvider::class,
    \Mezzio\Helper\ConfigProvider::class,
    \Mezzio\Router\FastRouteRouter\ConfigProvider::class,
    \Laminas\HttpHandlerRunner\ConfigProvider::class,
    \Mezzio\Swoole\ConfigProvider::class, // <-- Here or later!
    // Include cache configuration
    new \Laminas\ConfigAggregator\ArrayProvider($cacheConfig),
    \Mezzio\Helper\ConfigProvider::class,
    \Mezzio\ConfigProvider::class,
    \Mezzio\Router\ConfigProvider::class,
    \Laminas\Diactoros\ConfigProvider::class,
    // Default App module config
    \App\ConfigProvider::class,
    // Load application config in a pre-defined order in such a way that local settings
    // overwrite global settings. (Loaded as first to last):
    //   - `global.php`
    //   - `*.global.php`
    //   - `local.php`
    //   - `*.local.php`
    new \Laminas\ConfigAggregator\PhpFileProvider(realpath(__DIR__) . '/autoload/{{,*.}global,{,*.}local}.php'),
    // Load development config if it exists
    new \Laminas\ConfigAggregator\PhpFileProvider(realpath(__DIR__) . '/development.config.php'),
], $cacheConfig['config_cache_path']);
```

Note the change in position for the `Mezzio\Swoole\ConfigProvider:class` entry in the above example.
