<?php

/*
|--------------------------------------------------------------------------
| Create The Application
| 創建 Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
| 我們要做的第一件事就是建立一個新的 Laravel application 實例，它用來把所有的 Laravel 組件
| 黏在一起，就如同膠水的存在，這個 IoC 容器會將系統中所有的部份都綁定。
*/

$app = new Illuminate\Foundation\Application(
    dirname(__DIR__)
);

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
| 綁定重要的介面
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
| 接下來，我們將一些重要的介面綁進容器中，在需要的時刻讓他們得以被解析。 kernel 可以處理即將來臨
| 的 request ，不論他們是從 web 還是 CLI 進來的。
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

/*
|--------------------------------------------------------------------------
| Return The Application
| 回傳 Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
| 這個腳本會回傳 application 實例。正因為腳本僅是用來創建實例，因此我們得以將創建實例的步驟從
| 實際執行 application 和回應 response 的部份分離。
*/

return $app;
