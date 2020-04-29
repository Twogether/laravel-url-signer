<?php
namespace Twogether\LaravelURLSigner\KeyProviders;

use Twogether\LaravelURLSigner\Contracts\KeyProvider;
use Twogether\LaravelURLSigner\Exceptions\PrivateKeyNotFound;
use Twogether\LaravelURLSigner\Exceptions\PublicKeyNotFound;

class DummyKeyProvider
    implements KeyProvider
{
    /**
     * @throws PrivateKeyNotFound
     */
    public function getPrivateKey($key_name = 'default'): string
    {
        throw new PrivateKeyNotFound;
    }

    /**
     * @throws PublicKeyNotFound
     */
    public function getPublicKey($key_name = 'default'): string
    {
        throw new PublicKeyNotFound;
    }
}