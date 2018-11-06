<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @package  Laravel
 * @author   Taylor Otwell <taylor@laravel.com>
 */

// 記錄開始時間
define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
| 註冊自動載入器
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| our application. We just need to utilize it! We'll simply require it
| into the script here so that we don't have to worry about manual
| loading any of our classes later on. It feels great to relax.
|
| Composer 提供了便利且可自動產生的 class 載入器給我們，我們只需加以利用即可。
| 在此我們僅需 require ，後續就不用再擔心是否需要手動載入。這真的是很爽的一件事。
*/

require __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Turn On The Lights
| 開個燈
|--------------------------------------------------------------------------
|
| We need to illuminate PHP development, so let us turn on the lights.
| This bootstraps the framework and gets it ready for use, then it
| will load up this application so that we can run it and send
| the responses back to the browser and delight our users.
|
| 我們需要照亮 PHP 開發之路，讓我們開燈吧。
| 導引架框做好準備，載入 application 讓我們使用，回應 response 給 browser 讓使用者喜吱吱。
*/

$app = require_once __DIR__.'/../bootstrap/app.php';

/*
|--------------------------------------------------------------------------
| Run The Application
| Application 跑起來
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request
| through the kernel, and send the associated response back to
| the client's browser allowing them to enjoy the creative
| and wonderful application we have prepared for them.
|
| 一旦有了 application ，透過 kernel 處理 request 後，回應相關的 response 給 client 的
| browser ，並讓他們在我們精心準備的 application 中歡愉。
*/

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// capture 覆寫 request ，並用利用原始參數設定到 clone 的 Request
// 包括一些 GET POST JSON 參數的處理
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// 送出 response
$response->send();

$kernel->terminate($request, $response);
