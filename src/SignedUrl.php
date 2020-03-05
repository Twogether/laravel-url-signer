<?php
namespace Twogether\LaravelURLSigner;

class SignedUrl
{
    private $url;
    private $config = 'default';
    private $source = '';
    private $key = '';

    public function __construct(string $url)
    {
        $this->url = $url;
        return $this;
    }

    public function withConfig(string $config)
    {
        $this->config = $config;
        return $this;
    }

    public function withKey(string $key)
    {
        $this->key = $key;
        return $key;
    }

    public function withSource(string $source)
    {
        $this->source = $source;
        return $this;
    }

    public function __toString()
    {
        return URLSigner::sign($this->url,$this->config,$this->source,$this->key);
    }

    public function get()
    {
        return $this->__toString();
    }


}
