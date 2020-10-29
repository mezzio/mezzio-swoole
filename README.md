# mezzio-swoole

[![Build Status](https://travis-ci.com/mezzio/mezzio-swoole.svg?branch=master)](https://travis-ci.com/mezzio/mezzio-swoole)
[![Coverage Status](https://coveralls.io/repos/github/mezzio/mezzio-swoole/badge.svg?branch=master)](https://coveralls.io/github/mezzio/mezzio-swoole?branch=master)

This library provides the support of [Swoole](https://www.swoole.co.uk/) into
an [Mezzio](https://docs.mezzio.dev/) application. This means you can
execute your Mezzio application using Swoole directly from the command line.


## Installation

Run the following to install this library:

```bash
$ composer require mezzio/mezzio-swoole
```

## Configuration

After installing mezzio-swoole, you will need to first enable the
component, and then optionally configure it.

We recommend adding a new configuration file to your autoload directory,
`config/autoload/swoole.local.php`. To begin with, use the following contents:

```php
<?php

use Mezzio\Swoole\ConfigProvider;

return array_merge((new ConfigProvider())(), []);
```

The above will setup the Swoole integration for your application.

By default, Swoole executes the HTTP server with host `127.0.0.1` on port
`8080`. You can change these values via configuration. Assuming you have the
above, modify it to read as follows:

```php
<?php

use Mezzio\Swoole\ConfigProvider;

return array_merge((new ConfigProvider())(), [
    'mezzio-swoole' => [
        'swoole-http-server' => [
            'host' => 'insert hostname to use here',
            'port' => 80, // use an integer value here
        ],
    ],
]);
```

> ### Mezzio skeleton 3.1.0 and later
>
> If you have built your application on the 3.1.0 or later version of the
> Mezzio skeleton, you do not need to instantiate and invoke the package's
> `ConfigProvider`, as the skeleton supports it out of the box.
>
> You will only need to provide any additional configuration of the HTTP server.

## Execute

Once you have performed the configuration steps as outlined above, you can run an Mezzio application with Swoole via the [laminas-cli](https://docs.laminas.dev/laminas-cli) integration:

```bash
$ ./vendor/bin/laminas mezzio:swoole:start
```

Call the `laminas` command without arguments to get a list of available commands, looking for those that begin with `mezzio:swoole:`, and use the `help` meta-argument to get help on individual commands:

```bash
$ ./vendor/bin/laminas help mezzio:swoole:start
```

## Documentation

Browse the documentation online at https://docs.mezzio.dev/mezzio-swoole/

## Support

* [Issues](https://github.com/mezzio/mezzio-swoole/issues/)
* [Chat](https://laminas.dev/chat/)
* [Forum](https://discourse.laminas.dev/)
