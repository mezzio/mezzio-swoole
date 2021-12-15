# Migration

This document covers changes between version 3 and version 4, and how you may
update your code to adapt to them.

## SwooleRequestHandlerRunner

Version 3 pinned to the version 1 series of [laminas/laminas-httphandlerrunner](https://docs.laminas.dev/laminas-httphandlerrunner/), which defined the class `Laminas\HttpHandlerRunner\RequestHandlerRunner`.
mezzio-swoole extended that class via its own implementation `Mezzio\Swoole\SwooleRequestHandlerRunner`.

Version 4 of mezzio-swoole pins to version 2 of laminas/laminas-httphandlerrunner.
laminas-httphandlerrunner v2 introduces `Laminas\HttpHandlerRunner\RequestHandlerInterface`, and has its `Laminas\HttpHandlerRunner\RequestHandlerRunner` class implement this new interface; additionally, the class now marks itself **final**, which prevents extension, and thus requires a change in mezzio-swoole's implementation.

Additionally, the base [Mezzio package](https://docs.mezzio.dev/mezzio/) adopted laminas-httphandlerrunner v2.1 as its minimum supported version starting with its 3.8.0 release, and modified its `Mezzio\Application` class constructor to typehint against `Laminas\HttpHandlerRunner\RequestHandlerInterface` instead of `Laminas\HttpHandlerRunner\RequestHandlerRunner`.
The result is that mezzio-swoole v3 releases cannot be used with Mezzio versions 3.8 and newer.

For version 4, `Mezzio\Swoole\SwooleRequestHandlerRunner` now implements `Laminas\HttpHandlerRunner\RequestHandlerInterface`, but **does not** extend `Laminas\HttpHandlerRunner\RequestHandlerRunner` (as it cannot).
Further, the class is now marked **final**.

As such, you can no longer extend `Mezzio\Swoole\SwooleRequestHandlerRunner`.
To change behavior of the class, we recommend using its [event system](events.md) and attaching listeners to shape its behavior.
