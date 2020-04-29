<?php
namespace Twogether\LaravelURLSigner\Middleware;

use Closure;
use Twogether\LaravelURLSigner\Exceptions\InvalidSignedUrl;

class SignedURL
{
    public function handle($request, Closure $next, $keyName = 'default')
    {
        try {
            app('Twogether\URLSigner')->validate($request->fullUrl(),$keyName);
        } catch(InvalidSignedUrl $exception) {
            return response()->json($exception->errors(),401);
        }

        return $next($request);
    }
}