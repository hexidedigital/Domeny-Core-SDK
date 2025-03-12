<?php

namespace Hexidedigital\DomenyCoreSdk;


use Hexidedigital\DomenyCoreSdk\Extensions\ApiUserAuthProvider;
use Illuminate\Support\ServiceProvider;

class DomenyCoreSDKServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadConfig();
        $this->loadProviders();
    }

    private function loadProviders()
    {
        \Auth::provider(
            'api-user',
            fn ($app, array $config) => new ApiUserAuthProvider()
        );
    }

    private function loadConfig(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->packagePath('config/config.php') => config_path('domeny-sdk.php'),
            ], 'config');
        }
    }

    public function register(): void
    {
        $this->registerConfig();
        $this->registerFacades();
    }



    private function registerConfig(): void
    {
        $this->mergeConfigFrom($this->packagePath('config/config.php'), 'domeny-sdk');
    }

    private function registerFacades(): void
    {
//        $this->bindFacade('seo-helper', new SeoHelper());
    }

    private function packagePath($path): string
    {
        return __DIR__ . "/../{$path}";
    }
}