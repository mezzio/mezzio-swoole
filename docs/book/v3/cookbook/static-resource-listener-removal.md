# Removing the StaticResourceRequestListener

By default, we ship with static resource handling enabled.
This is done by having the `Mezzio\Swoole\Event\StaticResourceRequestListener` in the list of listeners provided for the `Mezzio\Swoole\Event\RequestEvent`.

To disable that listener, you will need to **replace** the set of listeners for that event, to include only the `Mezzio\Swoole\Event\RequestHandlerRequestListener`. You can do that in your application configuration as follows:

```php
// in config/autoload/dependencies.global.php:

use Laminas\Stdlib\ArrayUtils\MergeReplaceKey;
use Mezzio\Swoole\Event;

return [
    // ...
    'mezzio-swoole' => [
        // ...
        'swoole-http-server' => [
            // ...
            'listeners' => [
                Event\RequestEvent::class => new MergeReplaceKey([
                    Event\RequestHandlerRequestListener::class,
                ]),
            ],
        ],
    ],
    // ...
];
```

