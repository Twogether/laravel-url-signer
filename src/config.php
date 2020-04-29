<?php

/**
 * Manage keys for the Twogether/LaravelURLSigner package.
 * Don't feel the need to load public and private keys if
 * you don't need them. You will likely only need either
 * one or the other unless you are both making and
 * receiving requests from this application.
 */


return [

    'app_name' => null, // defaults to Str::slug(config('app.name'))

    'keys' => [
        'default' => [
            'public' => env('SIGNED_URLS_PUBLIC_KEY'),
            'private' => env('SIGNED_URLS_PRIVATE_KEY')
        ],
    ],

];