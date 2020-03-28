<?php
namespace Twogether\LaravelURLSigner;

class SignedUrl
{
    private $url;
    private $config = 'default';
    private $source = '';
    private $key = '';
    private $expiry;

    public function __construct(string $url)
    {
        $this->url = $url;
        return $this;
    }

    public function withConfig(string $config): SignedUrl
    {
        $this->config = $config;
        return $this;
    }

    public function withKey(string $key): SignedUrl
    {
        $this->key = $key;
        return $this;
    }

    public function withExpiry(int $expiry): SignedUrl
    {
        $this->expiry = $expiry;
        return $this;
    }

    public function withSource(string $source): SignedUrl
    {
        $this->source = $source;
        return $this;
    }

    public function __toString(): string
    {
        return URLSigner::sign($this->url,$this->config,$this->source,$this->key,$this->expiry ?: time()+120);
    }

    public function get(): string
    {
        return $this->__toString();
    }


}
