<?php
namespace Twogether\LaravelURLSigner\Contracts;

use Twogether\LaravelURLSigner\Exceptions\PrivateKeyNotFound;
use Twogether\LaravelURLSigner\Exceptions\PublicKeyNotFound;

interface KeyProvider
{
    /**
     * @throws PublicKeyNotFound
     */
    public function getPublicKey($key_name = 'default'): string;

    /**
     * @throws PrivateKeyNotFound
     */
    public function getPrivateKey($key_name = 'default'): string;
}