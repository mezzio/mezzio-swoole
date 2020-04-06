# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 2.7.0 - TBD

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.6.3 - TBD

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

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
