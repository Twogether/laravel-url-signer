<?php
namespace Twogether\LaravelURLSigner\CacheBrokers;

use Twogether\LaravelURLSigner\Contracts\CacheBroker;

class StaticCacheBroker implements CacheBroker
{
    private $keys = [];

    public function setNx(string $key, string $value = "1", int $seconds_to_expiry = 90): bool
    {
        if($this->keys[$key] ?? null === $value) {
            return false;
        }
        $this->keys[$key] = $value;
        return true;
    }

    public function get(string $key)
    {
        return $this->keys[$key] ?? null;
    }

    public function incr(string $key, int $seconds_to_expiry = 90): int
    {
        $this->keys[$key] = $this->keys[$key] ?? 0 + 1;
        return $this->keys[$key];
    }
}