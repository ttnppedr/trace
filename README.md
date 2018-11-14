待解決問題
===
- 名詞、動作及其作用釐清： build, make, resolve, bind, register, instance, contextual binding, alias, abstract type, concrete type, shared, bound, booted, buildStack, with
- topic: Application, Container, ServiceProvider, Kernel, Bootstrappers, Dispatch, Pipeline, Middleware, Route, ParameterBag, Request, Response, Output buffers
- clear or remove before bootstrap or instance

## Application properties modified timing
- $instances : Container->instance(), Container->resolve()
- $serviceProviders : Application->markAsRegistered()
- $loadedProviders : Application->markAsRegistered()
- $aliases : Container->alias()
- $abstractAliases : Container->alias()
- $bindings : Container->bind()
- $resolved : Container->resolve()
- $buildStack : Container->build()

## Application properties after constructor
- $basePath : "/Users/ttnppedr/code/trace"
- $instances :

```php
        [
            "path" => "/Users/ttnppedr/code/trace/app",
            "path.base" => "/Users/ttnppedr/code/trace",
            "path.lang" => "/Users/ttnppedr/code/trace/resources/lang",
            "path.config" => "/Users/ttnppedr/code/trace/config",
            "path.public" => "/Users/ttnppedr/code/trace/public",
            "path.storage" => "/Users/ttnppedr/code/trace/storage",
            "path.database" => "/Users/ttnppedr/code/trace/database",
            "path.resources" => "/Users/ttnppedr/code/trace/resources",
            "path.bootstrap" => "/Users/ttnppedr/code/trace/bootstrap",
            "app" => Application,
            "Illuminate\Container\Container" => Application,
            "Illuminate\Foundation\PackageManifest" => Application
        ]
```

- $hasBeenBootstrapped : false
- $booted : false
- $bootingCallbacks : [&nbsp;]
- $bootedCallbacks : [&nbsp;]
- $terminatingCallbacks : [&nbsp;]
- $serviceProviders :

```php
        [
            EventServiceProvider,
            LogServiceProvider,
            RoutingServiceProvider
        ]
```

- $loadedProviders :

```php
        [
            "Illuminate\Events\EventServiceProvider" => true,
            "Illuminate\Log\LogServiceProvider" => true,
            "Illuminate\Routing\RoutingServiceProvider" => true,
        ]
```

- $deferredServices : [&nbsp;]
- $databasePath : null
- $storagePath : null
- $environmentPath : null
- $environmentFile : ".env"
- $namespace : null
- $aliases :

```php
        [
            "Illuminate\Foundation\Application" => "app",
            "Illuminate\Contracts\Container\Container" => "app",
            "Illuminate\Contracts\Foundation\Application" => "app",
            "Psr\Container\ContainerInterface" => "app",
            "Illuminate\Auth\AuthManager" => "auth",
            "Illuminate\Contracts\Auth\Factory" => "auth",
            "Illuminate\Contracts\Auth\Guard" => "auth.driver",
            "Illuminate\View\Compilers\BladeCompiler" => "blade.compiler",
            "Illuminate\Cache\CacheManager" => "cache",
            "Illuminate\Contracts\Cache\Factory" => "cache",
            "Illuminate\Cache\Repository" => "cache.store",
            "Illuminate\Contracts\Cache\Repository" => "cache.store",
            "Illuminate\Config\Repository" => "config",
            "Illuminate\Contracts\Config\Repository" => "config",
            "Illuminate\Cookie\CookieJar" => "cookie",
            "Illuminate\Contracts\Cookie\Factory" => "cookie",
            "Illuminate\Contracts\Cookie\QueueingFactory" => "cookie",
            "Illuminate\Encryption\Encrypter" => "encrypter",
            "Illuminate\Contracts\Encryption\Encrypter" => "encrypter",
            "Illuminate\Database\DatabaseManager" => "db",
            "Illuminate\Database\Connection" => "db.connection",
            "Illuminate\Databese\ConnectionInterface" => "db.connection",
            "Illuminate\Events\Dispatcher" => "events",
            "Illuminate\Contracts\Events\Dispatcher" => "events",
            "Illuminate\Filesystem\Filesystem" => "files",
            "Illuminate\Filesystem\FilesystemManager" => "filesystem",
            "Illuminate\Contracts\Filesystem\Factory" => "filesystem",
            "Illuminate\Contracts\Filesystem\Filesystem" => "filesystem.disk",
            "Illuminate\Contracts\Filesystem\Cloud" => "filesystem.cloud",
            "Illuminate\Hashing\HashManager" => "hash",
            "Illuminate\Contracts\Hashing\Hasher" => "hash.driver",
            "Illuminate\Translation\Translator" => "translator",
            "Illuminate\Contracts\Translation\Translator" => "translator",
            "Illuminate\Log\LogManager" => "log",
            "Psr\Log\LoggerInterface" => "log",
            "Illuminate\Mail\Mailer" => "mailer",
            "Illuminate\Contracts\Mail\Mailer" => "mailer",
            "Illuminate\Contracts\Mail\MailQueue" => "mailer",
            "Illuminate\Auth\Passwords\PasswordBrokerManager" => "auth.password",
            "Illuminate\Contracts\Auth\PasswordBrokerFactory" => "auth.password",
            "Illuminate\Auth\Passwords\PasswordBroker" => "auth.password.broker",
            "Illuminate\Contracts\Auth\PasswordBroker" => "auth.password.broker",
            "Illuminate\Queue\QueueManager" => "queue",
            "Illuminate\Contracts\Queue\Factory" => "queue",
            "Illuminate\Contracts\Queue\Monitor" => "queue",
            "Illuminate\Contracts\Queue\Queue" => "queue.connection",
            "Illuminate\Queue\Failed\FailedJobProviderInterface" => "queue.failer",
            "Illuminate\Routing\Redirector" => "redirect",
            "Illuminate\Redis\RedisManager" => "redis",
            "Illuminate\Contracts\Redis\Factory" => "redis",
            "Illuminate\Http\Request" => "request",
            "Symfony\Component\HttpFoundation\Request" => "request",
            "Illuminate\Routing\Router" => "router",
            "Illuminaet\Contracts\Routing\Registrar" => "router",
            "Illuminate\Contracts\Routing\BindingRegistrar" => "router",
            "Illuminate\Session\SessionManager" => "session",
            "Illuminate\Session\Store" => "session.store",
            "Illuminate\Contracts\Session\Session" => "session.store",
            "Illuminate\Routing\UrlGenerator" => "url",
            "Illuminate\Contracts\Routing\UrlGenerator" => "url",
            "Illuminate\Validation\Factory" => "validator",
            "Illuminate\Contracts\Validation\Factory" => "validator",
            "Illuminate\View\Factory" => "view",
            "Illuminate\Contracts\View\Factory" => "view"
        ]
```

- $abstractAliases :

```php
        [
            "app" => [
                "Illuminate\Foundation\Application",
                "Illuminate\Contracts\Container\Container",
                "Illuminate\Contracts\Foundation\Application",
                "Psr\Container\ContainerInterface"
            ],
            "auth" => [
                "Illuminate\Auth\AuthManager",
                "Illuminate\Contracts\Auth\Factory"
            ],
            "auth.driver" => [
                "Illuminate\Contracts\Auth\Guard"
            ],
            "blade.compiler" => [
                "Illuminate\View\Compilers\BladeCompiler"
            ],
            "cache" => [
                "Illuminate\Cache\CacheManager",
                "Illuminate\Contracts\Cache\Factory"
            ],
            "cache.store" => [
                "Illuminate\Cache\Repository",
                "Illuminate\Contracts\Cache\Repository"
            ],
            "config" => [
                "Illuminate\Config\Repository",
                "Illuminate\Contracts\Config\Repository"
            ],
            "cookie" => [
                "Illuminate\Cookie\CookieJar",
                "Illuminate\Contracts\Cookie\Factory",
                "Illuminate\Contracts\Cookie\QueueingFactory"
            ],
            "encrypter" => [
                "Illuminate\Encryption\Encrypter",
                "Illuminate\Contracts\Encryption\Encrypter"
            ],
            "db" => [
                "Illuminate\Database\DatabaseManager"
            ],
            "db.connection" => [
                "Illuminate\Database\Connection",
                "Illuminate\Databese\ConnectionInterface"
            ],
            "events" => [
                "Illuminate\Events\Dispatcher",
                "Illuminate\Contracts\Events\Dispatcher"
            ],
            "files" => [
                "Illuminate\Filesystem\Filesystem"
            ],
            "filesystem" => [
                "Illuminate\Filesystem\FilesystemManager",
                "Illuminate\Contracts\Filesystem\Factory"
            ],
            "filesystem.disk" => [
                "Illuminate\Contracts\Filesystem\Filesystem"
            ],
            "filesystem.cloud" => [
                "Illuminate\Contracts\Filesystem\Cloud"
            ],
            "hash" => [
                "Illuminate\Hashing\HashManager"
            ],
            "hash.driver" => [
                "Illuminate\Contracts\Hash\Hasher"
            ],
            "translator" => [
                "Illuminate\Translation\Translator",
                "Illuminate\Contracts\Translation\Translator"
            ],
            "log" => [
                "Illuminate\Log\LogManager",
                "Psr\Log\LoggerInterface"
            ],
            "mailer" => [
                "Illuminate\Mail\Mailer",
                "Illuminate\Contracts\Mail\Mailer",
                "Illuminate\Contracts\Mail\MailQueue"
            ],
            "auth.password" => [
                "Illuminate\Auth\Passwords\PasswordBrokerManager",
                "Illuminate\Contracts\Auth\PasswordBrokerFactory"
            ],
            "auth.password.broker" => [
                "Illuminate\Auth\Passwords\PasswordBroker",
                "Illuminate\Contracts\Auth\PasswordBroker"
            ],
            "queue" => [
                "Illuminate\Queue\QueueManager",
                "Illuminate\Contracts\Queue\Factory",
                "Illuminate\Cnotracts\Queue\Monitor"
            ],
            "queue.connection" => [
                "Illuminate\Contracts\Queue\Queue"
            ],
            "queue.failer" => [
                "Illuminate\Queue\Failed\FailedJobProviderInterface"
            ],
            "redirect" => [
                "Illuminate\Routing\Redirector"
            ],
            "redis" => [
                "Illuminate\Redis\RedisManager",
                "Illuminate\Contracts\Redis\Factory"
            ],
            "request" => [
                "Illuminate\Http\Request",
                "Symfony\Component\HttpFoundation\Request"
            ],
            "router" => [
                "Illuminate\Routing\Router",
                "Illuminate\Contracts\Routing\Registrar",
                "Illuminate\Contracts\Routing\BindingRegistrar"
            ],
            "session" => [
                "Illuminate\Session\SessionManager"
            ],
            "session.store" => [
                "Illuminate\Session\Store",
                "Illuminate\Contracts\Session\Session"
            ],
            "url" => [
                "Illuminate\Routing\UrlGenerator",
                "Illuminate\Contracts\Routing\UrlGenerator"
            ],
            "validator" => [
                "Illuminate\Validation\Factory",
                "Illuminate\Contracts\Validation\Factory"
            ],
            "view" => [
                "Illuminate\View\Factory",
                "Illuminate\Contracts\View\Factory"
            ]
        ]
```