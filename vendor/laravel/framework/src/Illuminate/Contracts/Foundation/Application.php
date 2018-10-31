<?php

namespace Illuminate\Contracts\Foundation;

use Illuminate\Contracts\Container\Container;

interface Application extends Container
{
    /**
     * Get the version number of the application.
     * 取得 application 的版本號
     *
     * @return string
     */
    public function version();

    /**
     * Get the base path of the Laravel installation.
     * 取得安全 Laravel 的基本路徑
     *
     * @return string
     */
    public function basePath();

    /**
     * Get or check the current application environment.
     * 取得或檢查 application 的環境
     *
     * @return string
     */
    public function environment();

    /**
     * Determine if the application is running in the console.
     * 判定 application 是否在 console 中執行
     *
     * @return bool
     */
    public function runningInConsole();

    /**
     * Determine if the application is running unit tests.
     * 判定 application 是否在執行 unit tests
     *
     * @return bool
     */
    public function runningUnitTests();

    /**
     * Determine if the application is currently down for maintenance.
     * 判定 application 是否正在維護模式中
     *
     * @return bool
     */
    public function isDownForMaintenance();

    /**
     * Register all of the configured providers.
     * 註冊所有已配置的 providers
     *
     * @return void
     */
    public function registerConfiguredProviders();

    /**
     * Register a service provider with the application.
     * 在 application 中註冊一個 service provider
     *
     * @param  \Illuminate\Support\ServiceProvider|string  $provider
     * @param  bool   $force
     * @return \Illuminate\Support\ServiceProvider
     */
    public function register($provider, $force = false);

    /**
     * Register a deferred provider and service.
     * 註冊延遲載入的 provider 及 service
     *
     * @param  string  $provider
     * @param  string|null  $service
     * @return void
     */
    public function registerDeferredProvider($provider, $service = null);

    /**
     * Boot the application's service providers.
     * 啟動 application 的 service providers
     *
     * @return void
     */
    public function boot();

    /**
     * Register a new boot listener.
     * 註冊一個新的啟動監聽器
     *
     * @param  mixed  $callback
     * @return void
     */
    public function booting($callback);

    /**
     * Register a new "booted" listener.
     * 註冊一個新的已啟動的監聽器
     *
     * @param  mixed  $callback
     * @return void
     */
    public function booted($callback);

    /**
     * Get the path to the cached services.php file.
     * 取得快取 services.php 的路徑
     *
     * @return string
     */
    public function getCachedServicesPath();

    /**
     * Get the path to the cached packages.php file.
     * 取得快取 packages.php 的路徑
     *
     * @return string
     */
    public function getCachedPackagesPath();
}
