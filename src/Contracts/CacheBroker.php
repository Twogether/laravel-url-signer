<?php
namespace Twogether\LaravelURLSigner\Contracts;

interface CacheBroker
{
    public function setNx(string $key,string $value = "1", int $seconds_to_expiry = 90): bool;

    public function get(string $key);

    public function incr(string $key,int $seconds_to_expiry = 90): int;
}
