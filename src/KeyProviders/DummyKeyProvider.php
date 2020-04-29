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
    public function getPrivateKey($keyName = 'default'): string
    {
        throw new PrivateKeyNotFound;
    }

    /**
     * @throws PublicKeyNotFound
     */
    public function getPublicKey($keyName = 'default', $sourceName = null): string
    {
        throw new PublicKeyNotFound;
    }
}