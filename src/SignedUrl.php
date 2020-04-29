<?php
namespace Twogether\LaravelURLSigner;

use Twogether\LaravelURLSigner\Contracts\CacheBroker;
use Twogether\LaravelURLSigner\Contracts\KeyProvider;
use Twogether\LaravelURLSigner\Exceptions\InvalidUrl;
use Twogether\LaravelURLSigner\Exceptions\PrivateKeyNotFound;

class SignedUrl
{
    private $cacheBroker;
    private $keyProvider;
    private $url;
    private $source = '';
    private $key = '';
    private $keyName = 'default';
    private $expiry;

    public function __construct(string $url)
    {
        $this->url = $url;
        return $this;
    }

    public function setCacheBroker(CacheBroker $cacheBroker): SignedUrl
    {
        $this->cacheBroker = $cacheBroker;
        return $this;
    }

    public function withKeyName(string $keyName): SignedUrl
    {
        $this->keyName = $keyName;
        return $this;
    }

    public function setKeyProvider(KeyProvider $keyProvider): SignedUrl
    {
        $this->keyProvider = $keyProvider;
        return $this;
    }

    public function withKey(string $key): SignedUrl
    {
        $this->key = $key;
        return $this;
    }

    public function withExpiry(int $expiry): SignedUrl
    {
        $this->expiry = $expiry;
        return $this;
    }

    public function withSource(string $source): SignedUrl
    {
        $this->source = $source;
        return $this;
    }

    public function __toString(): string
    {
        return $this->get();
    }

    public function get(): string
    {
        $parts = parse_url($this->url);

        $key = $this->getKey();

        if(!array_key_exists('scheme',$parts) || !array_key_exists('host',$parts)) {
            throw new InvalidUrl;
        }

        $url = $parts['scheme']."://".$parts['host'].($parts['path'] ?? '');

        parse_str($parts['query'] ?? '',$args);

        $args['ac_xp'] = $this->expiry ?: time() + 120;
        $args['ac_ts'] = time();
        $args['ac_nc'] = $this->cacheBroker->incr('signed-urls-signing-nonce_'.$args['ac_ts'],300);
        $args['ac_sc'] = $this->source;

        ksort($args);

        openssl_sign(
            http_build_query($args),
            $signature,
            KeyFormatter::fromString($key,true),
            OPENSSL_ALGO_SHA256
        );

        $args['ac_sg'] = base64_encode($signature);

        return $url."?".http_build_query($args);

    }

    private function getKey(): string
    {
        if($this->key) {
            return $this->key;
        }

        if(!$this->keyProvider) {
            throw new \Exception("No Key, and no Key Provider specified");
        }

        return $this->keyProvider->getPrivateKey($this->keyName);

    }


}
