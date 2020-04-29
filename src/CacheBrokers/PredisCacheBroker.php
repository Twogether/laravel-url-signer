<?php
namespace Twogether\LaravelURLSigner\CacheBrokers;

use Predis\Client;
use Twogether\LaravelURLSigner\Contracts\CacheBroker;

class PredisCacheBroker
    implements CacheBroker
{
    private $predis;

    public function __construct(Client $predis) {
        $this->predis = $predis;
    }

    public function setNx(string $key, string $value = "1", int $seconds_to_expiry = 90): bool
    {
        $return = $this->predis->setNx($key,$value);
        if($return) {
            $this->predis->expire($key,$seconds_to_expiry);
            return true;
        }
        return false;
    }

    public function get(string $key)
    {
        return $this->predis->get($key);
    }

    public function incr(string $key, int $seconds_to_expiry = 90): int
    {
        $value = $this->predis->incr($key);
        $this->predis->expire($key,$seconds_to_expiry);

        return $value;
    }
}