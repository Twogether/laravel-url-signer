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

The private key goes somewhere into the Source config, and the public
key goes into the Target config.


## Signing

Now when you want to make a request from the Source, generate your
target URL, and pass it through:

`URLSigner::sign(string $url, string $privateKey [, string $source_name])`

This will return your URL with some additional security parameters
and a signature added. The `$source_name` is optional and defaults
to `config('app.name')`.  Make sure this is unique to your Source
if you intend to make calls from multiple apps to the same Target.


## Validating

`URLSigner::validate(string $url, string $publicKey)` will validate
the signed URL and return a `ValidationResult` object with two
methods.

`isValid(): bool`
True or false if the URL was validated correctly.

`errors(): array`
An array of validation errors, keyed by field to work with a
Laravel Form Request. You may prefer to use Middleware. Note that
the list of errors is not exhaustive, if something's wrong it
will bomb out immediately and only list the last error unless
everything was missing.

## Middleware

To get started quickly on your Targets, make sure the public key
is accessible at `config('auth.twogether_url_public_key')` and
just add:

`Twogether\LaravelURLSigner\Middleware\SignedURL:class`

to a Middleware stack.

---

#### Note on keys

Your public and private keys can either be a one line string, or
the proper 64-character per-line versions with -----BEGIN----- and 
-----END-----. The library will handle either.