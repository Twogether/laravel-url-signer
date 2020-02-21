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
        try {
            URLSigner::validate($request->fullUrl(),$config);
        } catch(InvalidSignedUrl $exception) {
            return response()->json($exception->errors(),401);
        }

        return $next($request);
    }
}