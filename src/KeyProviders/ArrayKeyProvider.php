<?php
namespace Twogether\LaravelURLSigner\KeyProviders;

use Twogether\LaravelURLSigner\Contracts\KeyProvider;
use Twogether\LaravelURLSigner\Exceptions\PrivateKeyNotFound;
use Twogether\LaravelURLSigner\Exceptions\PublicKeyNotFound;

class ArrayKeyProvider
    implements KeyProvider
{
    private $keys;

    public function __construct(array $keys)
    {
        $this->keys = $keys;
    }

    /**
     * @throws PrivateKeyNotFound
     */
    public function getPrivateKey($keyName = 'default'): string
    {
        if(array_key_exists($keyName,$this->keys) && ($this->keys[$keyName]['private'] ?? false)) {
            return $this->keys[$keyName]['private'];
        }
        throw new PrivateKeyNotFound;
    }

    /**
     * @throws PublicKeyNotFound
     */
    public function getPublicKey($keyName = 'default', $sourceName = null): string
    {
        if(array_key_exists($keyName,$this->keys) && ($this->keys[$keyName]['public'] ?? false)) {
            return $this->keys[$keyName]['public'];
        }
        throw new PublicKeyNotFound;
    }
}