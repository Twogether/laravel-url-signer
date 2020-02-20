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
    }
}