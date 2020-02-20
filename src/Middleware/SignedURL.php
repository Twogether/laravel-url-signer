<?php
namespace Twogether\LaravelURLSigner\Middleware;

use Closure;
use Twogether\LaravelURLSigner\URLSigner;

class SignedURL
{
    public function handle($request, Closure $next)
    {
        $result = URLSigner::validate($request->fullUrl,config('auth.twogether_url_public_key'));

        if(!$result->isValid()) {
            return response()->json($result->errors(),401);
        }

        return $next($request);
    }
}