<?php
namespace Twogether\LaravelURLSigner;

use Faker\Provider\Person;

class GeneratedKeyPair
{
    private $privateKey;
    private $publicKey;

    public function __construct()
    {
        $raw = openssl_pkey_new(array(
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ));

        openssl_pkey_export($raw,$privateKey);
        $this->privateKey = $privateKey;
        $this->publicKey = openssl_pkey_get_details($raw)['key'];

    }

    private function stringify($key)
    {
        $key = preg_replace("/-----(BEGIN|END) (PUBLIC|PRIVATE) KEY-----/","",$key);
        $key = str_replace("\n","",$key);
        return trim($key);
    }

    public function getPrivate($stringify = false)
    {
        return $stringify ? $this->stringify($this->privateKey) : $this->privateKey;
    }

    public function getPublic($stringify = false)
    {
        return $stringify ? $this->stringify($this->publicKey) : $this->publicKey;
    }
}