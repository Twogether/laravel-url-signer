<?php
namespace Twogether\LaravelURLSigner\Contracts;

use Twogether\LaravelURLSigner\Exceptions\PrivateKeyNotFound;
use Twogether\LaravelURLSigner\Exceptions\PublicKeyNotFound;

interface KeyProvider
{
    /**
     * @throws PublicKeyNotFound
     */
    public function getPublicKey($keyName = 'default', $sourceName = null): string;

    /**
     * @throws PrivateKeyNotFound
     */
    public function getPrivateKey($keyName = 'default'): string;
}