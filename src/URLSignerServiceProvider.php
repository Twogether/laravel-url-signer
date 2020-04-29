<?php
namespace Twogether\LaravelURLSigner;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Twogether\LaravelURLSigner\CacheBrokers\LaravelCacheBroker;
use Twogether\LaravelURLSigner\KeyProviders\ConfigKeyProvider;

class URLSignerServiceProvider
    extends ServiceProvider
{
    public function boot()
    {
        $this->commands([
            GenerateKeyPairCommand::class
        ]);

        $this->publishes([
            __DIR__.'/config.php' => config_path('signed_urls.php'),
        ]);

        $this->app['router']->aliasMiddleware('signed_url',\Twogether\LaravelURLSigner\Middleware\SignedURL::class);
    }

    public function register()
    {
        app()->singleton('Twogether\URLSigner',function() {
            return new SignedUrlFactory(
                config('signed_urls.app_name',Str::slug(config('app.name'))),
                new LaravelCacheBroker(),
                new ConfigKeyProvider()
            );
        });
    }
}