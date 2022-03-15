<?php
namespace Twogether\LaravelURLSigner\CacheBrokers;

use Twogether\LaravelURLSigner\Contracts\CacheBroker;

class RedisCacheBroker
	implements CacheBroker
{
	protected $redis;

	public function setNx(string $key, string $value = "1", int $seconds_to_expiry = 90): bool
	{
		$return = $this->redis->setNx($key,$value);
		if($return) {
			$this->redis->expire($key,$seconds_to_expiry);
			return true;
		}
		return false;
	}

	public function get(string $key)
	{
		return $this->redis->get($key);
	}

	public function incr(string $key, int $seconds_to_expiry = 90): int
	{
		$value = $this->redis->incr($key);
		$this->redis->expire($key,$seconds_to_expiry);

		return $value;
	}
}