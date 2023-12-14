<?php
namespace Twogether\LaravelURLSigner;

use Twogether\LaravelURLSigner\Contracts\CacheBroker;
use Twogether\LaravelURLSigner\Contracts\KeyProvider;
use Twogether\LaravelURLSigner\Exceptions\InvalidSignedUrl;
use Twogether\LaravelURLSigner\KeyProviders\DummyKeyProvider;

class SignedUrlFactory
{
    private $cacheBroker;
    private $keyProvider;
    private $appName;

    public function __construct(string $appName, CacheBroker $cacheBroker, KeyProvider $keyProvider = null)
    {
        $this->appName = str_replace(' ','',strtolower($appName));
        $this->cacheBroker = $cacheBroker;
        $this->keyProvider = $keyProvider ?: new DummyKeyProvider;
    }

    public function setKeyProvider(KeyProvider $keyProvider)
    {
        $this->keyProvider = $keyProvider;
    }

    public static function reconstituteUrl(array $parts, array $params)
    {
        $url = $parts['scheme']."://".$parts['host'].($parts['path'] ?? '');
        if(count($params)) {
            $url .= "?".http_build_query($params);
        }
        return $url;
    }

    public function make(string $url, string $keyName = 'default'): SignedUrl
    {
        return (new SignedUrl($url))
            ->withKeyName($keyName)
            ->setKeyProvider($this->keyProvider)
            ->withSource($this->appName);
    }

    public function sign(string $url, string $keyName = 'default'): string
    {
        return $this->make($url,$keyName)->get();
    }

    public function validate(string $url, string $keyName = 'default', string $publicKey = ''): bool
    {
        $errors = [
            'ac_ts' => 'Timestamp is missing',
            'ac_nc' => 'Nonce is missing',
            'ac_sg' => 'Signature is missing',
            'ac_sc' => 'Source identifier is missing',
            'ac_xp' => 'Expiry is missing',
        ];

        $url_parts = parse_url($url);
        $url_parts['scheme'] = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? $url_parts['scheme'];

        $query = $url_parts['query'] ?? null;

        if(!$query) {
            throw new InvalidSignedUrl($errors);
        }

        parse_str($query,$params);

        $errors = array_diff_key($errors,$params);

        if(count($errors)) {
            throw new InvalidSignedUrl($errors);
        }

        // All parameters are present

        // Check timestamp

        if(!is_numeric($params['ac_ts']) || $params['ac_ts'] > time() + 120) {
            throw new InvalidSignedUrl(['ac_ts' => 'Timestamp is invalid']);
        }

        // Check expiry

        if(!is_numeric($params['ac_xp']) || $params['ac_xp'] < time()) {
            throw new InvalidSignedUrl(['ac_xp' => 'URL has expired']);
        }

        // Check nonce has not been used
        $nonce_key = implode('|',['signed-urls-signed-nonce',$params['ac_sc'],$params['ac_ts'],$params['ac_nc']]);

        if(!$this->cacheBroker->setNx($nonce_key,1,$params['ac_xp'] - time())) {
            throw new InvalidSignedUrl(['ac_nc' => 'Nonce for '.$params['ac_sc'].' has been used already']);
        }

        // Check signature

        $signature = $params['ac_sg'];
        unset($params['ac_sg']);
        ksort($params);

        $valid = openssl_verify(
            static::reconstituteUrl($url_parts,$params),
            base64_decode($signature),
            KeyFormatter::fromString($publicKey ?: $this->keyProvider->getPublicKey($keyName,$params['ac_sc']),false),
            OPENSSL_ALGO_SHA256
        );

        if(!$valid) {
            throw new InvalidSignedUrl(['ac_sg' => 'Signature is invalid']);
        }

        return true;
    }


}