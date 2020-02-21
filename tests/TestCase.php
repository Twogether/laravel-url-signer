<?php
namespace Twogether\LaravelURLSignerTests;

use Illuminate\Support\Facades\Redis;
use Twogether\LaravelURLSigner\URLSignerServiceProvider;

class TestCase
    extends \Orchestra\Testbench\TestCase
{
    public function setUp(): void
    {
        parent::setUp();

    }

    protected function getResource($name)
    {
        return trim(file_get_contents(__DIR__."/../resources/".$name));
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.redis.client', 'predis');
    }

    protected function getPackageAliases($app)
    {
        return [
            'Redis' => Redis::class
        ];
    }

    protected function getPackageProviders($app)
    {
        return [
            URLSignerServiceProvider::class
        ];
    }
}