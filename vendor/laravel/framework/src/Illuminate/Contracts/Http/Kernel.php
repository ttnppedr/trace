<?php

namespace Illuminate\Contracts\Http;

interface Kernel
{
    /**
     * Bootstrap the application for HTTP requests.
     * 啟動處理 HTTP request 的 application
     *
     * @return void
     */
    public function bootstrap();

    /**
     * Handle an incoming HTTP request.
     * 處理傳入的 HTTP request
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle($request);

    /**
     * Perform any final actions for the request lifecycle.
     * 執行 request lifecycle 的最後一步
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return void
     */
    public function terminate($request, $response);

    /**
     * Get the Laravel application instance.
     * 取得 Laravel application 的實例
     *
     * @return \Illuminate\Contracts\Foundation\Application
     */
    public function getApplication();
}
