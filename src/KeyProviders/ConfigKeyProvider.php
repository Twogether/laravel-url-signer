<?php
namespace Twogether\LaravelURLSigner\KeyProviders;

use Twogether\LaravelURLSigner\Contracts\KeyProvider;
use Twogether\LaravelURLSigner\Exceptions\PrivateKeyNotFound;
use Twogether\LaravelURLSigner\Exceptions\PublicKeyNotFound;

class ConfigKeyProvider
    implements KeyProvider
{
    /**
     * @throws PrivateKeyNotFound
     */
    public function getPrivateKey($key_name = 'default'): string
    {
        $key = config('signed_urls.keys.'.$key_name.'.private');
        if(!$key) {
            throw new PrivateKeyNotFound;
        }
        return $key;
    }

    /**
     * @throws PublicKeyNotFound
     */
    public function getPublicKey($key_name = 'default'): string
    {
        $key = config('signed_urls.keys.'.$key_name.'.public');
        if(!$key) {
            throw new PublicKeyNotFound;
        }
        return $key;
    }
}