# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 3.1.2 - 2021-01-05


-----

### Release Notes for [3.1.2](https://github.com/mezzio/mezzio-swoole/milestone/17)

3.1.x bugfix release (patch)

### 3.1.2

- Total issues resolved: **1**
- Total pull requests resolved: **1**
- Total contributors: **2**

#### Bug

 - [52: Remove &quot;bin&quot; configuration from package](https://github.com/mezzio/mezzio-swoole/pull/52) thanks to @weierophinney and @demijohn

## 3.1.1 - 2020-12-14


-----

### Release Notes for [3.1.1](https://github.com/mezzio/mezzio-swoole/milestone/15)

3.1.x bugfix release (patch)

### 3.1.1

- Total issues resolved: **1**
- Total pull requests resolved: **1**
- Total contributors: **2**

#### Bug

 - [49: Update `ReloadCommand` to use full command names to stop/start the server](https://github.com/mezzio/mezzio-swoole/pull/49) thanks to @weierophinney and @demijohn

## 3.1.0 - 2020-12-02

### Changed

- [#47](https://github.com/mezzio/mezzio-swoole/pull/47) changes the visibility of the `$server` and `$dispatcher` properties of `Mezzio\Swoole\SwooleRequestHandlerRunner` to `protected`, to allow those extending the class the ability to work with the instances directly. This can be useful when using 3rd party Swoole listeners, such as the one provided by New Relic.


-----

### Release Notes for [3.1.0](https://github.com/mezzio/mezzio-swoole/milestone/12)

Feature release (minor)

### 3.1.0

- Total issues resolved: **1**
- Total pull requests resolved: **1**
- Total contributors: **2**

#### Enhancement

 - [47: Allow access to dispatcher and Swoole HTTP Server instances in extending classes](https://github.com/mezzio/mezzio-swoole/pull/47) thanks to @weierophinney and @arku31

## 3.0.1 - 2020-11-09

### Fixed

- [#42](https://github.com/mezzio/mezzio-swoole/pull/42) fixes issues with the `AccessLogDataMap` whereby fatal errors would be raised for attempts to access uninitialized properties (e.g., the `$psrResponse` property, when the `Psr3AccessLogDecorator` created an `AccessLogDataMap` from a static resource response).

-----

### Release Notes for [3.0.1](https://github.com/mezzio/mezzio-swoole/milestone/11)

- Total issues resolved: **0**
- Total pull requests resolved: **1**
- Total contributors: **1**

#### Bug

 - [42: Ensure AccessLogDataMap does not throw fatal error due to uninitialized typed properties](https://github.com/mezzio/mezzio-swoole/pull/42) thanks to @weierophinney

## 3.0.0 - 2020-10-29

### Added

- [#40](https://github.com/mezzio/mezzio-swoole/pull/40) adds the `--num-task-workers|-t` option to each of the `mezzio:swoole:start` and `mezzio:swoole:reload` commands, allowing you to specify these at the command-line instead of in configuration.

- Adds a variety of event types based on Swoole HTTP Server events, as well as default listeners, a [PSR-14](https://www.php-fig.org/psr/psr-14) listener provider to aggregate them, and a simple PSR-14 dispatcher implementation for triggering them. See the [events chapter of the documentation](https://docs.mezzio.dev/mezzio-swoole/v3/events/) for more details.

### Changed

- [#41](https://github.com/mezzio/mezzio-swoole/pull/41) adds typehints to all properties that can safely add them. If you were extending classes from the package and overriding any properties, please check the canonical versions and update your definitions accordingly.

- [#39](https://github.com/mezzio/mezzio-swoole/pull/39) switches the console tooling to remove the `mezzio-swoole` binary in favor of using [laminas-cli](https://docs.laminas.dev/laminas-cli/). The individual commands are renamed from `start`, `stop`, `status`, and `reload` to `mezzio:swoole:start`, `mezzio:swoole:stop`, `mezzio:swoole:status`, and `mezzio:swoole:reload`, respectively.

- Rewrites the internals of `Mezzio\Swoole\SwooleRequestHandlerRunner` to trigger events via a [PSR-14](https://www.php-fig.org/psr/psr-14) event dispatcher. See the [events chapter of the documentation](https://docs.mezzio.dev/mezzio-swoole/v3/events/) for more details.

### Removed

- [#28](https://github.com/mezzio/mezzio-swoole/pull/28) removes the class `Mezzio\Swoole\HotCodeReload\Reloader` and its associated factory. Users should use `Mezzio\Swoole\Event\HotCodeReloaderWorkerStartListener` instead. In most cases, this will be a non-issue, as it was used internally only in the past.

- Removes support for services named after legacy Zend Framework/Expressive classes.

- Removes support for Swoole versions prior to 4.5.

- Removes support for PHP versions prior to 7.4.


-----

### Release Notes for [3.0.0](https://github.com/mezzio/mezzio-swoole/milestone/5)



### 3.0.0

- Total issues resolved: **4**
- Total pull requests resolved: **5**
- Total contributors: **2**

#### BC Break,Enhancement

 - [41: Add property typehints](https://github.com/mezzio/mezzio-swoole/pull/41) thanks to @weierophinney
 - [39: Migrate console tooling to laminas-cli](https://github.com/mezzio/mezzio-swoole/pull/39) thanks to @weierophinney
 - [28: Fully evented request handler implementation](https://github.com/mezzio/mezzio-swoole/pull/28) thanks to @weierophinney

#### Enhancement

 - [40: Allow the start and reload commands to specify the number of task workers to spawn](https://github.com/mezzio/mezzio-swoole/pull/40) thanks to @weierophinney

#### BC Break,Feature Removal,Feature Request

 - [32: Migrate commands to laminas-cli](https://github.com/mezzio/mezzio-swoole/issues/32) thanks to @weierophinney

#### Awaiting Author Updates,Enhancement

 - [24: add stub code to show implement event-dispatcher on swoole worker event](https://github.com/mezzio/mezzio-swoole/pull/24) thanks to @eranoitulover and @weierophinney

## 2.8.1 - 2020-10-23

### Fixed

- [#31](https://github.com/mezzio/mezzio-swoole/pull/31) provides a fix to the `SwooleEmitter to allow it to work properly with callback streams. It does so by only rewinding the stream if it is seekable, and calling `Swoole\Htp\Response::end()` with the discovered `$body` directly if it is not marked readable (forcing it into string representation).

-----

### Release Notes for [2.8.1](https://github.com/mezzio/mezzio-swoole/milestone/8)

- Total issues resolved: **0**
- Total pull requests resolved: **1**
- Total contributors: **1**

#### Bug

 - [31: Enable SwooleEmitter to work with CallbackStreams](https://github.com/mezzio/mezzio-swoole/pull/31) thanks to @basz

## 2.8.0 - 2020-10-20

### Added

- [#33](https://github.com/mezzio/mezzio-swoole/pull/33) adds support for PHP 8.

### Removed

- [#33](https://github.com/mezzio/mezzio-swoole/pull/33) removes support for Swoole versions prior to 4.5.5.

- [#33](https://github.com/mezzio/mezzio-swoole/pull/33) removes support for PHP 7.2.

-----

### Release Notes for [2.8.0](https://github.com/mezzio/mezzio-swoole/milestone/7)

- Total issues resolved: **1**
- Total pull requests resolved: **2**
- Total contributors: **2**

#### Enhancement

 - [34: Psalm integration](https://github.com/mezzio/mezzio-swoole/pull/34) thanks to @weierophinney and @boesing
 - [33: PHP 8 Support](https://github.com/mezzio/mezzio-swoole/pull/33) thanks to @weierophinney

## 2.7.0 - 2020-09-22

### Added

- [#22](https://github.com/mezzio/mezzio-swoole/pull/22) adds a new `StaticMappedResourceHandler`, allowing developers to specify resources outside the `public/` tree that can be served as static assets; in particular, module authors could utilize this to deliver assets within their module directory tree. See the [StaticMappedResourceHandler documentation](https://docs.mezzio.dev/mezzio-swoole/v2/static-resources/#example-alternate-static-resource-handler-staticmappedresourcehandler) for more details.


-----

### Release Notes for [2.7.0](https://github.com/mezzio/mezzio-swoole/milestone/4)



### 2.7.0

- Total issues resolved: **1**
- Total pull requests resolved: **4**
- Total contributors: **3**

#### Enhancement

 - [27: Remove PHPUnit warnings](https://github.com/mezzio/mezzio-swoole/pull/27) thanks to @weierophinney
 - [26: Bump laminas-coding-standard from 1.0 to 2.1](https://github.com/mezzio/mezzio-swoole/pull/26) thanks to @weierophinney
 - [25: Switch to composer/package-versions-deprecated](https://github.com/mezzio/mezzio-swoole/pull/25) thanks to @weierophinney and @boesing

#### Documentation,Enhancement

 - [22: Add Mapped Document Roots functionality](https://github.com/mezzio/mezzio-swoole/pull/22) thanks to @jasonterando

## 2.6.6 - 2020-06-17

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#16](https://github.com/mezzio/mezzio-swoole/pull/16) fixes an issue whereby `Mezzio\Swoole\SwooleStream` was truncating the last byte of a stream.

## 2.6.5 - 2020-05-27

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#13](https://github.com/mezzio/mezzio-swoole/pull/13) adds a missing dependency definition for the `InotifyFileWatcher`; this addition ensures that hot code reloading will work out-of-the-box when enabled.

## 2.6.4 - 2020-05-04

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#10](https://github.com/mezzio/mezzio-swoole/pull/10) improves how client IP addresses are detected when printing access logs, by taking into consideration `x-real-ip`, `client-ip` and `x-forwarded-for` headers, in that order.

## 2.6.3 - 2020-04-17

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#8](https://github.com/mezzio/mezzio-swoole/pull/8) removes an undefined variable from a method call within `AccessLogDataMap::getResponseMessageSize()`, fixing a notice.

## 2.6.2 - 2020-04-06

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#7](https://github.com/mezzio/mezzio-swoole/pull/7) fixes default value for SameSite in Cookie of Swoole\Http\Response.

## 2.6.1 - 2020-03-28

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Fixed `replace` version constraint in composer.json so repository can be used as replacement of `zendframework/zend-expressive-swoole:^2.5.0`.

## 2.6.0 - 2020-02-05

### Added

- [#4](https://github.com/mezzio/mezzio-swoole/pull/4) adds support for PHP 7.4.

- [#4](https://github.com/mezzio/mezzio-swoole/pull/4) adds explicit ext-swoole requirement to version ^4.4.6 which supports SameSite cookie directive.

- [#4](https://github.com/mezzio/mezzio-swoole/pull/4) adds support for SameSite cookie directive in SwooleEmitter.

### Changed

- [#4](https://github.com/mezzio/mezzio-swoole/pull/4) changes minimum required version of dflydev/fig-cookies to ^2.0.1 which supports SameSite cookie directive.

### Deprecated

- Nothing.

### Removed

- [#4](https://github.com/mezzio/mezzio-swoole/pull/4) removes support for PHP 7.1.

- [#4](https://github.com/mezzio/mezzio-swoole/pull/4) removes support for dflydev/fig-cookies v1 releases.

### Fixed

- Nothing.

## 2.5.0 - 2019-11-22

### Added

- [zendframework/zend-expressive-swoole#73](https://github.com/zendframework/zend-expressive-swoole/pull/73) adds
  compatibility with symfony/console `^5.0`.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.4.1 - 2019-11-13

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive-swoole#67](https://github.com/zendframework/zend-expressive-swoole/pull/67) fixes the `HttpServerFactory` to properly support Swoole coroutines.

## 2.4.0 - 2019-03-05

### Added

- [zendframework/zend-expressive-swoole#64](https://github.com/zendframework/zend-expressive-swoole/pull/64) updates the hot reloading feature so that it logs messages using the same
  logger configured for access logging.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.3.0 - 2019-02-07

### Added

- [zendframework/zend-expressive-swoole#60](https://github.com/zendframework/zend-expressive-swoole/pull/60) adds a new configuration, `mezzio-swoole.hot-code-reload`.
  Configuring hot-code-reload allows the Swoole HTTP server to monitor for
  changes in included PHP files, and reload accordingly.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.2.1 - 2019-02-07

### Added

- [zendframework/zend-expressive-swoole#56](https://github.com/zendframework/zend-expressive-swoole/pull/56) adds support for PHP 7.3.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.2.0 - 2018-12-03

### Added

- [zendframework/zend-expressive-swoole#55](https://github.com/zendframework/zend-expressive-swoole/pull/55) adds a new configuration key, `mezzio-swoole.swoole-http-server.logger.logger-name`.
  It allows a custom service name which resolves to a `Psr\Log\LoggerInterface`
  instance to be provided, in order to be wrapped in the
  `Mezzio\Swoole\Log\Psr3AccessLogDecorator`:

  ```php
  return [
      'mezzio-swoole' => [
          'swoole-http-server' => [
              'logger' => [
                  'logger-name' => 'my_logger',
              ],
          ],
      ],
  ];
  ```

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.1.0 - 2018-11-28

### Added

- [zendframework/zend-expressive-swoole#54](https://github.com/zendframework/zend-expressive-swoole/pull/54) adds a new configuration key, `mezzio-swoole.swoole-http-server.process-name`.
  This value will be used as a prefix for the process name of all processes
  created by the `Swoole\Http\Server` instance, including the master process,
  worker processes, and all task worker processes. The value defaults to
  `mezzio`. As an example:

  ```php
  return [
      'mezzio-swoole' => [
          'swoole-http-server' => [
              'process-name' => 'myapp',
          ],
      ],
  ];
  ```

- [zendframework/zend-expressive-swoole#50](https://github.com/zendframework/zend-expressive-swoole/pull/50) adds a new configuration flag for toggling serving of static files:
  `mezzio-swoole.swoole-http-server.static-files.enable`. The flag is
  enabled by default; set it to boolean `false` to disable static file serving:

  ```php
  return [
      'mezzio-swoole' => [
          'swoole-http-server' => [
              'static-files' => [
                  'enable' => false,
              ],
          ],
      ],
  ];
  ```

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.0.1 - 2018-11-28

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive-swoole#48](https://github.com/zendframework/zend-expressive-swoole/pull/48) adds a `shutdown` handler to the Swoole HTTP server that clears the PID
  manager, ensuring the PID file is cleared.

- [zendframework/zend-expressive-swoole#52](https://github.com/zendframework/zend-expressive-swoole/pull/52) fixes an error thrown by the `start` command when using this component in
  configuration-driven mezzio applications, due to the fact that the command
  always tried to require the `config/pipeline.php` and `config/routes.php`
  files.

## 2.0.0 - 2018-11-15

### Added

- [zendframework/zend-expressive-swoole#46](https://github.com/zendframework/zend-expressive-swoole/pull/46) adds a new command for the command line tooling, `status`; the command
  simply tells you if the server is running or not.

- [zendframework/zend-expressive-swoole#43](https://github.com/zendframework/zend-expressive-swoole/pull/43) adds the class `Mezzio\Swoole\WhoopsPrettyPageHandlerDelegator`,
  and registers it to the service `Mezzio\WhoopsPageHandler`. The
  delegator calls `handleUnconditionally()` on the handler in order to ensure it
  will operate under the CLI SAPI that Swoole runs under.

- [zendframework/zend-expressive-swoole#40](https://github.com/zendframework/zend-expressive-swoole/pull/40) adds the class `Mezzio\Swoole\HttpServerFactory`, which
  generates a `Swoole\Http\Server` instance based on provided configuration; it
  replaces the former `Mezzio\Swoole\ServerFactory` (as well as the
  factory for that class). The new factory class is now registered as a factory
  for the `Swoole\Http\Server` class, which allows users to further configure
  the Swoole server instance via delegators, specifically for the purpose of
  [enabling async task workers](https://docs.mezzio.dev/mezzio-swoole/v2/async-tasks/).

### Changed

- [zendframework/zend-expressive-swoole#46](https://github.com/zendframework/zend-expressive-swoole/pull/46) moves the command line utilities for controlling the web server out of
  the application runner, and into a new vendor binary, `mezzio-swoole`
  (called via `./vendor/bin/mezzio-swoole`). This change was required
  to allow us to expose the `Swoole\Http\Server` instance as a service, and has
  the added benefit that `reload` operations now will fully stop and start the
  server, allowing it to pick up configuration and code changes. **You will need
  to update any deployment scripts to use the new vendor binary.**

- [zendframework/zend-expressive-swoole#40](https://github.com/zendframework/zend-expressive-swoole/pull/40) changes how you configure Swoole's coroutine support. Previously, you
  would toggle the configuration flag `mezzio-swoole.swoole-http-server.options.enable_coroutine`;
  you should now use the flag `mezzio-swoole.enable_coroutine`. The
  original flag still exists, but is now used to toggle coroutine support in the
  `Swoole\Http\Server` instance specifically.

- [zendframework/zend-expressive-swoole#42](https://github.com/zendframework/zend-expressive-swoole/pull/42) adds a discrete factory service for the `SwooleRequestHandlerRunner`, and now aliases
  `Laminas\HttpHandlerRunner\RequestHandlerRunner` to that service.

### Deprecated

- Nothing.

### Removed

- [zendframework/zend-expressive-swoole#40](https://github.com/zendframework/zend-expressive-swoole/pull/40) removes the `Mezzio\Swoole\ServerFactory` and
  `ServerFactoryFactory` classes, as well as the `Mezzio\Swoole\ServerFactory`
  service. Users should instead reference the `Swoole\Http\Server` service,
  which is now registered via the `Mezzio\Swoole\HttpServerFactory`
  factory, detailed in the "Added" section above.

### Fixed

- Nothing.

## 1.0.2 - 2018-11-13

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive-swoole#45](https://github.com/zendframework/zend-expressive-swoole/pull/45) provides a patch that ensures that SSL support can be enabled when
  creating the `Swoole\Http\Server` instance. SSL support requires not just the
  SSL certificate and private key, but also providing a protocol of either
  `SWOOLE_SOCK_TCP | SWOOLE_SSL` or `SWOOLE_SOCK_TCP6 | SWOOLE_SSL`.
  Previously, the union types would raise an exception during instantiation.

## 1.0.1 - 2018-11-08

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive-swoole#41](https://github.com/zendframework/zend-expressive-swoole/pull/41) fixes an issue that occurs when the HTTP request body is empty.
  `Swoole\Http\Request::rawcontent()` returns `false` in such situations, when a
  string is expected. `Mezzio\Swoole\SwooleStream` now detects this and
  casts to an empty string.

## 1.0.0 - 2018-10-02

### Added

- [zendframework/zend-expressive-swoole#38](https://github.com/zendframework/zend-expressive-swoole/pull/38) adds documentation covering potential issues when using a long-running
  server such as Swoole, as well as how to avoid them.

- [zendframework/zend-expressive-swoole#38](https://github.com/zendframework/zend-expressive-swoole/pull/38) adds documentation covering how to use Monolog as a PSR-3 logger for the
  Swoole server.

- [zendframework/zend-expressive-swoole#38](https://github.com/zendframework/zend-expressive-swoole/pull/38) adds a default value of 1024 for the `max_conn` Swoole HTTP server option.
  By default, Swoole uses the value of `ulimit -n` on the system; however, in
  containers and virtualized environments, this value often reports far higher
  than the host system can allow, which can lead to resource problems and
  termination of the server. Setting a default ensures the component can work
  out-of-the-box for most situations. Users should consult their host machine
  specifications and set an appropriate value in production.

### Changed

- [zendframework/zend-expressive-swoole#38](https://github.com/zendframework/zend-expressive-swoole/pull/38) versions the documentation, moving all URLS below the `/v1/` subpath.
  Redirects from the original pages to the new ones were also added.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.2.4 - 2018-10-02

### Added

- [zendframework/zend-expressive-swoole#37](https://github.com/zendframework/zend-expressive-swoole/pull/37) adds support for zendframework/zend-diactoros 2.0.0. You may use either
  a 1.Y or 2.Y version of that library with Expressive applications.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive-swoole#36](https://github.com/zendframework/zend-expressive-swoole/pull/36) fixes the call to `emitMarshalServerRequestException()` to ensure the
  request is passed to it.

## 0.2.3 - 2018-09-27

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive-swoole#35](https://github.com/zendframework/zend-expressive-swoole/pull/35) fixes logging when unable to marshal a server request.

## 0.2.2 - 2018-09-05

### Added

- [zendframework/zend-expressive-swoole#28](https://github.com/zendframework/zend-expressive-swoole/pull/28) adds a new option, `mezzio-swoole.swoole-http-server.options.enable_coroutine`.
  The option is only relevant for Swoole 4.1 and up. When enabled, this option
  will turn on coroutine support, which essentially wraps most blocking I/O
  operations (including PDO, Mysqli, Redis, SOAP, `stream_socket_client`,
  `fsockopen`, and `file_get_contents` with URIs) into coroutines, allowing
  workers to handle additional requests while waiting for the operations to
  complete.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.2.1 - 2018-09-04

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive-swoole#30](https://github.com/zendframework/zend-expressive-swoole/pull/30) fixes how the `Content-Length` header is passed to the Swoole response, ensuring we cast the value to a string.

## 0.2.0 - 2018-08-30

### Added

- [zendframework/zend-expressive-swoole#26](https://github.com/zendframework/zend-expressive-swoole/pull/26) adds comprehensive access logging capabilities via a new subnamespace,
  `Mezzio\Swoole\Log`. Capabilities include support (most) of the
  Apache log format placeholders (as well as the standard formats used by Apache
  and Debian), and the ability to provide your own formatting mechanisms. Please
  see the [logging documentation](https://docs.mezzio.dev/mezzio-swoole/logging/)
  for more information.

- [zendframework/zend-expressive-swoole#20](https://github.com/zendframework/zend-expressive-swoole/pull/20) adds a new interface, `Mezzio\Swoole\StaticResourceHandlerInterface`,
  and default implementation `Mezzio\Swoole\StaticResourceHandler`,
  used to determine if a request is for a static file, and then to serve it; the
  `SwooleRequestHandlerRunner` composes an instance now for providing static
  resource serving capabilities.

  The default implementation uses custom middleware to allow providing common
  features such as HTTP client-side caching headers, handling `OPTIONS`
  requests, etc. Full capabilities include:

  - Filtering by allowed extensions.
  - Emitting `405` statuses for unsupported HTTP methods.
  - Handling `OPTIONS` requests.
  - Handling `HEAD` requests.
  - Providing gzip/deflate compression of response content.
  - Selectively emitting `Cache-Control` headers.
  - Selectively emitting `Last-Modified` headers.
  - Selectively emitting `ETag` headers.

  Please see the [static resource documentation](https://docs.mezzio.dev/mezzio-swoole/static-resources/)
  for more information.

- [zendframework/zend-expressive-swoole#11](https://github.com/zendframework/zend-expressive-swoole/pull/11), [zendframework/zend-expressive-swoole#18](https://github.com/zendframework/zend-expressive-swoole/pull/18), and [zendframework/zend-expressive-swoole#22](https://github.com/zendframework/zend-expressive-swoole/pull/22) add the following console actions and options to
  interact with the server via `public/index.php`:
  - `start` will start the server; it may be omitted, as this is the default action.
    - `--dameonize|-d` tells the server to daemonize itself when `start` is called.
    - `--num_workers|w` tells the server how many workers to spawn when starting (defaults to 4).
  - `stop` will stop the server.
  - `reload` reloads all worker processes, but only when the mezzio-swoole.swoole-http-server.mode
    configuration value is set to `SWOOLE_PROCESS`.

### Changed

- [zendframework/zend-expressive-swoole#21](https://github.com/zendframework/zend-expressive-swoole/pull/21) renames `RequestHandlerSwooleRunner` (and its related factory) to `SwooleRequestHandlerRunner`.

- [zendframework/zend-expressive-swoole#20](https://github.com/zendframework/zend-expressive-swoole/pull/20) and [zendframework/zend-expressive-swoole#26](https://github.com/zendframework/zend-expressive-swoole/pull/26) modify the collaborators and thus constructor arguments
  expected by the `SwooleRequestHandlerRunner`. The constructor now has the
  following signature:

  ```php
  public function __construct(
      Psr\Http\Server\RequestHandlerInterface $handler,
      callable $serverRequestFactory,
      callable $serverRequestErrorResponseGenerator,
      Mezzio\Swoole\PidManager $pidManager,
      Mezzio\Swoole\ServerFactory $serverFactory,
      Mezzio\Swoole\StaticResourceHandlerInterface $staticResourceHandler = null,
      Mezzio\Swoole\Log\AccessLogInterface $logger = null
  ) {
  ```

  If you were manually creating an instance, or had provided your own factory,
  you will need to update your code.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.1.1 - 2018-08-14

### Added

- [zendframework/zend-expressive-swoole#5](https://github.com/zendframework/zend-expressive-swoole/pull/5) adds the ability to serve static file resources from your
  configured document root. For information on the default capabilities, as well
  as how to configure the functionality, please see
  https://docs.mezzio.dev/mezzio-swoole/intro/#serving-static-files.

### Changed

- [zendframework/zend-expressive-swoole#9](https://github.com/zendframework/zend-expressive-swoole/pull/9) modifies how the `RequestHandlerSwooleRunner` provides logging
  output.  Previously, it used `printf()` directly. Now it uses a [PSR-3
  logger](https://www.php-fig.org/psr/psr-3/) instance, defaulting to an
  internal implementation that writes to STDOUT. The logger may be provided
  during instantiation, or via the `Psr\Log\LoggerInterface` service.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive-swoole#7](https://github.com/zendframework/zend-expressive-swoole/pull/7) fixes how cookies are emitted by the Swoole HTTP server. We now
  use the server `cookie()` method to set cookies, ensuring that multiple
  cookies are not squashed into a single `Set-Cookie` header.

## 0.1.0 - 2018-07-10

### Added

- Everything.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.
