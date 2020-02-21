<?php
namespace Twogether\LaravelURLSignerTests;

use Twogether\LaravelURLSigner\KeyFormatter;

class FormattingTest
    extends TestCase
{
    public function test_a_basic_public_key_works()
    {
        $key = $this->getResource('dummy_public_key.txt');

        $this->assertEquals($key,KeyFormatter::fromString($key,false));
    }

    public function test_a_naked_public_key_works()
    {
        $key = $this->getResource('dummy_public_key.txt');
        $naked = $this->getResource('dummy_public_key_naked.txt');

        $this->assertEquals($key,KeyFormatter::fromString($naked,false));
    }


    public function test_a_basic_private_key_works()
    {
        $key = $this->getResource('dummy_private_key.txt');

        $this->assertEquals($key,KeyFormatter::fromString($key,true));
    }

    public function test_a_naked_private_key_works()
    {
        $key = $this->getResource('dummy_private_key.txt');
        $naked = $this->getResource('dummy_private_key_naked.txt');

        $this->assertEquals($key,KeyFormatter::fromString($naked,true));
    }
}