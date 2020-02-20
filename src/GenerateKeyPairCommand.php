<?php
namespace Twogether\LaravelURLSigner;

use Illuminate\Console\Command;

class GenerateKeyPairCommand
    extends Command
{
    protected $signature = 'twogether:generate-key-pair';

    protected $description = 'Generate a public/private key pair for URL signing';

    public function handle()
    {
        $raw = openssl_pkey_new(array(
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ));


        openssl_pkey_export($raw,$privateKey);
        $publicKey = openssl_pkey_get_details($raw)['key'];

        $this->warn('PRIVATE KEY');
        $this->info($this->stringify($privateKey));

        $this->warn('PUBLIC KEY');
        $this->info($this->stringify($publicKey));

    }

    private function stringify($key)
    {
        $key = preg_replace("/-----(BEGIN|END) (PUBLIC|PRIVATE) KEY-----/","",$key);
        $key = str_replace("\n","",$key);
        return trim($key);
    }
}