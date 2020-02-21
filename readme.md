# URL Signer

Signs and validates URLs with public/private keys. This is used
so that a _Source_ application can make calls to a _Target_
application and the Target can verify they're legit.

This package requires Laravel (or something very compatible) and
uses the Redis facade directly in both apps.

## Installing

First install the package. It adds an artisan command
which you can use to generate a key pair if you need one.

`php artisan twogether:generate-key-pair`

You'll want to install the package into both the Source and Target.


## Configuration

The private key goes somewhere into the Source config, and the public
key goes into the Target config.

You can either manage keys yourself and pass them into the signing
and validation methods directly at run-time. (Perhaps encrypt them in
a DB if your app is multi-tenant). Or if you do not pass in a key, it
will look for them in `config/signed_urls` where you can specify 
different config names if you have multiple keys to juggle.

An explicitly passed key will always override a named configuration.


## Signing

Now when you want to make a request from the Source, generate your
target URL, and pass it through:

`URLSigner::sign(string $url, string $config_name = 'default', string $privateKey = '' [, string $source_name])`

This will return your URL with some additional security parameters
and a signature added. The `$source_name` is optional and defaults
to `config('app.name')`.  Make sure this is unique to your Source
if you intend to make calls from multiple apps to the same Target.

Signed URLs must be used immediately, they are only valid for 120
seconds. They cannot be used more than once. Don't generate them
and then put them on a queue.


## Validating

`URLSigner::getValidationResult(string $url, string $config_name = 'default', string $publicKey = '')`
will validate the signed URL and return true if it passes.

If it fails, it will throw an InvalidSignedUrl exception which
has one method:

`errors(): array`
An array of validation errors, keyed by field to work with a
Laravel Form Request. You may prefer to use Middleware. Note that
the list of errors is not exhaustive, if something's wrong it
will bomb out immediately and only list the last error unless
everything was missing.

## Middleware

To get started quickly, publish the config file and then set up
a public or private key as the 'default' in `config/signed_urls`
and just add:

`Twogether\LaravelURLSigner\Middleware\SignedURL:class`

to a Middleware stack.

The middleware also supports a config name if you want to set
up your routes. Alias the middleware in your kernel to `signed_urls`
and then specify something like `signed_urls:reporting` to reference
the public key assigned to 'reporting' in your config.


---

#### Note on keys

Your public and private keys can either be a one line string, or
the proper 64-character per-line versions with -----BEGIN----- and 
-----END-----. The library will handle either.