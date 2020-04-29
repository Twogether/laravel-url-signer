<?php
namespace Twogether\LaravelURLSigner\CacheBrokers;

use Illuminate\Support\Facades\Redis;
use Twogether\LaravelURLSigner\Contracts\CacheBroker;

class LaravelCacheBroker
    implements CacheBroker
{

    public function setNx(string $key, string $value = "1", int $seconds_to_expiry = 90): bool
    {
        $return = Redis::setNx($key,$value);
        if($return) {
            Redis::expire($key,$seconds_to_expiry);
            return true;
        }
        return false;

    }

    public function get(string $key)
    {
        return Redis::get($key);
    }

    public function incr(string $key, int $seconds_to_expiry = 90): int
    {
        $value = Redis::incr($key);
        Redis::expire($key,$seconds_to_expiry);

        return $value;
    }
}
