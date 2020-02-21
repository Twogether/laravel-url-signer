<?php
namespace Twogether\LaravelURLSigner;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Twogether\LaravelURLSigner\Exceptions\InvalidSignedUrl;
use Twogether\LaravelURLSigner\Exceptions\PrivateKeyNotFound;
use Twogether\LaravelURLSigner\Exceptions\PublicKeyNotFound;

class URLSigner
{
    public static function sign(string $url,string $config_name = 'default', string $privateKey = '',string $source_app = ''): string
    {
        $parts = parse_url($url);

        if(!$privateKey) {

            $privateKey = config('signed_urls.private_keys.'.$config_name);

            if(!$privateKey) {
                throw new PrivateKeyNotFound();
            }
        }

        $url = "";

        if(array_key_exists('scheme',$parts)) {
            $url = $parts['scheme']."://";
        }

        if(array_key_exists('host',$parts)) {
            $url .= $parts['host'];
        }

        $url .= ($parts['path'] ?? '');

        parse_str($parts['query'] ?? '',$args);

        $args['ac_ts'] = time();
        $args['ac_nc'] = Redis::incr('nonce_'.$args['ac_ts']);
        $args['ac_sc'] = Str::slug($source_app ?: config('app.name'));

        Redis::expire('nonce_'.$args['ac_ts'],300);

        ksort($args);

        openssl_sign(
            http_build_query($args),
            $signature,
            KeyFormatter::fromString($privateKey,true),
            OPENSSL_ALGO_SHA256
        );

        $args['ac_sg'] = base64_encode($signature);

        return $url."?".http_build_query($args);
    }

    public static function validate($url, string $config_name = 'default', string $publicKey = ''): bool
    {
        if(!$publicKey) {
            $publicKey = config('signed_urls.public_keys.'.$config_name);
            if(!$publicKey) {
                throw new PublicKeyNotFound();
            }
        }

        $errors = [
            'ac_ts' => 'Timestamp is missing',
            'ac_nc' => 'Nonce is missing',
            'ac_sg' => 'Signature is missing',
            'ac_sc' => 'Source identifier is missing',
        ];

        $query = parse_url($url)['query'] ?? null;

        if(!$query) {
            throw new InvalidSignedUrl($errors);
        }

        parse_str($query,$params);

        $errors = array_diff_key($errors,$params);

        if(count($errors)) {
            throw new InvalidSignedUrl($errors);
        }

        // All parameters are present

        // Check timestamp
        if(!is_numeric($params['ac_ts']) || $params['ac_ts'] > time()+120 || $params['ac_ts'] < time() - 120) {
            throw new InvalidSignedUrl(['ac_ts' => 'Timestamp is invalid']);
        }

        // Check nonce has not been used
        $nonce_key = implode('|',['ac_nonce',$params['ac_sc'],$params['ac_ts'],$params['ac_nc']]);

        if(Redis::setNx($nonce_key,1)) {
            Redis::expire($nonce_key,120);
        } else {
            throw new InvalidSignedUrl(['ac_nc' => 'Nonce for '.$params['ac_sc'].' has been used already']);
        }

        // Check signature

        $signature = $params['ac_sg'];
        unset($params['ac_sg']);
        ksort($params);

        $valid = openssl_verify(
            http_build_query($params),
            base64_decode($signature),
            $publicKey,
            OPENSSL_ALGO_SHA256
        );

        if(!$valid) {
            throw new InvalidSignedUrl(['ac_sg' => 'Signature is invalid']);
        }

        return true;
    }

}
