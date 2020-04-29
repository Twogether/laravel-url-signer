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
    public function getPrivateKey($keyName = 'default'): string
    {
        $key = config('signed_urls.keys.'.$keyName.'.private');
        if(!$key) {
            throw new PrivateKeyNotFound;
        }
        return $key;
    }

    /**
     * @throws PublicKeyNotFound
     */
    public function getPublicKey($keyName = 'default', $sourceName = null): string
    {
        $key = config('signed_urls.keys.'.$keyName.'.public');
        if(!$key) {
            throw new PublicKeyNotFound;
        }
        return $key;
    }
}