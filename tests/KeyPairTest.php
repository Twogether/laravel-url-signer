<?php
namespace Twogether\LaravelURLSignerTests;

use Twogether\LaravelURLSigner\GeneratedKeyPair;

class KeyPairTest
    extends TestCase
{
    public function test_a_valid_pair_is_generated()
    {
        $pair = new GeneratedKeyPair();

        openssl_private_encrypt('test string',$encrypted,$pair->getPrivate());
        openssl_public_decrypt($encrypted,$decrypted,$pair->getPublic());

        $this->assertEquals('test string',$decrypted);



    }
}