<?php
namespace Twogether\LaravelURLSigner;

use Illuminate\Support\ServiceProvider;

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
    }
}