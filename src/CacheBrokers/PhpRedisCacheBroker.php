<?php
namespace Twogether\LaravelURLSigner\CacheBrokers;

use \Redis;

class PhpRedisCacheBroker
	extends RedisCacheBroker
{
	public function __construct(Redis $redis) {
		$this->redis = $redis;
	}
}