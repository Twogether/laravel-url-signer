<?php
namespace Twogether\LaravelURLSigner;

use Illuminate\Console\Command;

class GenerateKeyPairCommand
    extends Command
{
    protected $signature = 'twogether:generate-key-pair {--raw}';

    protected $description = 'Generate a public/private key pair for URL signing';

    public function handle()
    {
        $pair = new GeneratedKeyPair();

        $this->warn('PRIVATE KEY');
        $this->info($pair->getPrivate(!$this->option('raw')));

        $this->warn('PUBLIC KEY');
        $this->info($pair->getPublic(!$this->option('raw')));

    }
}