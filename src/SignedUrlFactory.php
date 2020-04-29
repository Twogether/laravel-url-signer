<?php
namespace Twogether\LaravelURLSigner;

use Twogether\LaravelURLSigner\Contracts\CacheBroker;
use Twogether\LaravelURLSigner\Contracts\KeyProvider;
use Twogether\LaravelURLSigner\Exceptions\InvalidSignedUrl;
use Twogether\LaravelURLSigner\Exceptions\PublicKeyNotFound;
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

    public function create(string $url, string $keyName = 'default')
    {
        return (new SignedUrl($url))
            ->withKeyName($keyName)
            ->setCacheBroker($this->cacheBroker)
            ->setKeyProvider($this->keyProvider)
            ->withSource($this->appName);

    }

    public function validate(string $url, string $keyName = 'default', string $publicKey = '')
    {
        $errors = [
            'ac_ts' => 'Timestamp is missing',
            'ac_nc' => 'Nonce is missing',
            'ac_sg' => 'Signature is missing',
            'ac_sc' => 'Source identifier is missing',
            'ac_xp' => 'Expiry is missing',
        ];

        $query = parse_url($url)['query'] ?? null;

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
            throw new InvalidSignedUrl(['ac_nc' => 'Nonce for '.$params['ac_sc'].' has been used already '.$nonce_key]);
        }

        // Check signature

        $signature = $params['ac_sg'];
        unset($params['ac_sg']);
        ksort($params);

        $valid = openssl_verify(
            http_build_query($params),
            base64_decode($signature),
            KeyFormatter::fromString($publicKey ?: $this->keyProvider->getPublicKey($keyName),false),
            OPENSSL_ALGO_SHA256
        );

        if(!$valid) {
            throw new InvalidSignedUrl(['ac_sg' => 'Signature is invalid']);
        }

        return true;
    }


}