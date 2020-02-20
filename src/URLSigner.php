<?php
namespace Twogether\LaravelURLSigner;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class URLSigner
{
    public static function sign(string $url,string $privateKey,string $source_app = null): string
    {
        $parts = parse_url($url);

        $url = $parts['scheme']."://".$parts['host'].($parts['path'] ?? '');

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

    public static function validate($url,$publicKey): ValidationResult
    {
        $errors = [
            'ac_ts' => 'Timestamp is missing',
            'ac_nc' => 'Nonce is missing',
            'ac_sg' => 'Signature is missing',
            'ac_sc' => 'Source identifier is missing',
        ];

        $query = parse_url($url)['query'] ?? null;

        if(!$query) {
            return new ValidationResult($errors);
        }

        parse_str($query,$params);

        $errors = array_diff_key($errors,$params);

        if(count($errors)) {
            return new ValidationResult($errors);
        }

        // All parameters are present

        // Check timestamp
        if(!is_numeric($params['ac_ts']) || $params['ac_ts'] > time()+120 || $params['ac_ts'] < time() - 120) {
            return new ValidationResult(['ac_ts' => 'Timestamp is invalid']);
        }

        // Check nonce has not been used
        $nonce_key = implode('|',['ac_nonce',$params['ac_sc'],$params['ac_ts'],$params['ac_nc']]);

        if(Redis::setNx($nonce_key,1)) {
            Redis::expire($nonce_key,120);
        } else {
            return new ValidationResult(['ac_nc' => 'Nonce for '.$params['ac_sc'].' has been used already']);
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
            return new ValidationResult(['ac_sg' => 'Signature is invalid']);
        }


        //parse_str(,$params);

        return new ValidationResult([]);
    }

}
