# mezzio-swoole

[![Build Status](https://github.com/mezzio/mezzio-swoole/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/mezzio/mezzio-swoole/actions/workflows/continuous-integration.yml)
[![type-coverage](https://shepherd.dev/github/mezzio/mezzio-swoole/coverage.svg)](https://shepherd.dev/github/mezzio/mezzio-swoole)
[![Psalm level](https://shepherd.dev/github/mezzio/mezzio-swoole/level.svg)](https://shepherd.dev/github/mezzio/mezzio-swoole)

> ## 🇷🇺 Русским гражданам
>
> Мы, участники Laminas, родились и живем в разных странах. У многих из нас есть друзья, родственники и коллеги как в России, так и в Украине. Некоторые из нас родились в России. Некоторые из нас живут в России. У некоторых бабушки и дедушки сражались с фашистами во Второй мировой войне. Здесь никто не поддерживает фашизм.
>
> У одного из нас есть украинская родственница, которая спаслась из дома вместе с сыном. Поезд задержался из-за бомбежки на дороге впереди. У нас есть друзья, которые прячутся в бомбоубежищах. Мы с тревогой ждем весточки от них после воздушных налетов, которые беспорядочно наносят удары и попадают по больницам, школам, детским садам и домам. Мы не берем это из каких-либо СМИ. Мы наблюдаем это напрямую.
>
> Вы доверяете нам достаточно, чтоб использовать наши программы, и мы просим вас довериться нам вновь. Мы нуждаемся в помощи. Выходите и протестуйте против этой бесполезной войны. Остановите кровопролитие. Скажите "Нет войне!"
>
> ## 🇺🇸 To Citizens of Russia
>
> We at Laminas come from all over the world. Many of us have friends, family and colleagues in both Russia and Ukraine. Some of us were born in Russia. Some of us currently live in Russia. Some have grandparents who fought Nazis in World War II. Nobody here supports fascism.
>
> One team member has a Ukrainian relative who fled her home with her son. The train was delayed due to bombing on the road ahead. We have friends who are hiding in bomb shelters. We anxiously follow up on them after the air raids, which indiscriminately fire at hospitals, schools, kindergartens and houses. We're not taking this from any media. These are our actual experiences.
>
> You trust us enough to use our software. We ask that you trust us to say the truth on this. We need your help. Go out and protest this unnecessary war. Stop the bloodshed. Say "stop the war!"

This library provides the support of [Swoole](https://github.com/swoole/swoole-src) into an [Mezzio](https://docs.mezzio.dev/) application.
This means you can execute your Mezzio application using Swoole directly from the command line.

## Installation

Run the following to install this library:

```bash
$ composer require mezzio/mezzio-swoole
```

## Configuration

After installing mezzio-swoole, you will need to first enable the component, and then optionally configure it.

We recommend adding a new configuration file to your autoload directory, `config/autoload/swoole.local.php`.
To begin with, use the following contents:

```php
<?php

use Mezzio\Swoole\ConfigProvider;

return array_merge((new ConfigProvider())(), []);
```

The above will setup Swoole integration for your application.

By default, Swoole executes the HTTP server with host `127.0.0.1` on port `8080`.
You can change these values via configuration.
Assuming you have the above, modify it to read as follows:

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
> If you have built your application on the 3.1.0 or later version of the Mezzio skeleton, you do not need to instantiate and invoke the package's `ConfigProvider`, as the skeleton supports it out of the box.
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
