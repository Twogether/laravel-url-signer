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
            if(config('app.debug') || $request->expectsJson()) {
                return response()->json($exception->errors(),401);
            } else {
                abort(401);
            }
        }

        return $next($request);
    }
}