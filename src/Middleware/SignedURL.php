<?php
namespace Twogether\LaravelURLSigner\Middleware;

use Closure;
use Twogether\LaravelURLSigner\Exceptions\InvalidSignedUrl;
use Twogether\LaravelURLSigner\Exceptions\PublicKeyNotFound;
use Twogether\LaravelURLSigner\URLSigner;

class SignedURL
{
    public function handle($request, Closure $next, $config = 'default')
    {
        $publicKey = config('signed_urls.public_keys.'.$config);

        if(!$publicKey) {
            throw new PublicKeyNotFound();
        }

        try {
            URLSigner::validate($request->fullUrl,$publicKey);
        } catch(InvalidSignedUrl $exception) {
            return response()->json($exception->errors(),401);
        }

        return $next($request);
    }
}