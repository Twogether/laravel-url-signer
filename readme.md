# URL Signer

Signs and validates URLs with public/private keys. This is used
so that a _Source_ application can make one time calls to a _Target_
application and the Target can verify they're legit.

This package is designed for Laravel, but can be used by other things
with some additional configuration.

## Installing

First install the package. If you're using Laravel, it adds an artisan
command which you can use to generate a key pair if you need one.

`php artisan twogether:generate-key-pair`

You'll want to install the package into both the Source and Target.

If you're not using Laravel, you will want to generate an OpenSSL
Public/Private Key Pair.


## Configuring the Factory

If you are using Laravel, the Service Provider will automatically
register a factory that uses a config file that publishes to
`config/signed_urls` to store keys, and uses the Redis facade.
`app('Twogether\URLSigner')` will return an instance of this.

If you are configuring manually, then you need to create a Factory.

```
new Twogether\LaravelURLSigner\SignedUrlFactory(
    string $appName, 
    CacheBroker $cacheBroker, 
    KeyProvider $keyProvider = null
)
```

App Name is simply a short string identifying this application. Avoid
using spaces, and keep it simple. The _Target_ will use this to verify
that requests from this source are unique, so you should not duplicate
app_names and use them with the same target.

The CacheBroker is used to generate and validate one-time _nonce_ codes
to prevent replay attacks. It expects to use Redis for this, and a
PredisCacheBroker class is available for you to use by passing in an
instance of `Predis\Client`. If you are not using Redis, or if you do
not use Predis, then you can check this class to see what it does and
implement your own by implementing the interface in `Contracts\CacheBroker`.

**Important note**

If you choose to implement your own CacheBroker please make sure you
understand exactly what the `setNx` and `incr` methods do in Redis. Every
URL generated during a one second interval must have a unique nonce
value, and these must be validated. That means your implementation needs
to be thread-safe, and must validate correctly. Otherwise URLs can be
replayed and this will cause you problems.

Lastly the KeyProvider is optional. If you do not choose to use it then
you will have to explicitly set the key every time you sign or validate
 a URL. We have provided an array implementation that you can configure:
 
 ```
 new Twogether\LaravelURLSigner\KeyProviders\ArrayKeyProvider([
    'default' => [
        'public' => '', // Public key string
        'private' => '', // Private key string
    ]
]);
```

You do not need to specify both public and private keys if you do not need
them. A _Target_ application that only receives requests and does not make
them does not need a private key for example. And vice versa.

If you want to sign or validate URLs with multiple services, you can
add additional `keyName => [pair]` entries to this array.


## Signing

Now when you want to make a request from the Source, generate your
target URL, and pass it through:

`$factory->create(string $url, string $keyName = 'default')`

This will return a `SignedUrl` object which you can further configure
if you want to specify additional options. e.g.

```
$factory->create('https://example.com')
    ->withKey(EXPLICIT PRIVATE KEY HERE)
    ->withExpiry(time()+300) // 5 minute expiry
```

Here we can explicitly set the private key if we did not configure a provider.
We can also specify that this URL will be valid for 5 minutes (default is 2).


## Validating

`$factory->validate($url,string $keyName = 'default', string $publicKey = '')`

will validate the signed URL and return true if it passes. You
can either specify a keyName for your KeyProvider to fetch the public
key, or pass the key as a string if you prefer.

If it fails, it will throw an InvalidSignedUrl exception which
has one method:

`errors(): array`
An array of validation errors, keyed by field to work with a
Laravel Form Request. You may prefer to use Middleware. Note that
the list of errors is not exhaustive, if something's wrong it
will error immediately and only include the last error unless
everything was missing.


## Get started with Laravel

To get started quickly, publish the config file and then set up
a public or private key in `keys.default` in `config/signed_urls`.

Now just add:

`Twogether\LaravelURLSigner\Middleware\SignedURL:class`

to a Middleware stack.

The middleware also supports a keyName if you want to set
up your routes. Alias the middleware in your kernel to `signed_urls`
and then specify something like `signed_urls:reporting` to use
the public key assigned to 'reporting' in your config.


---

#### Note on keys

Your public and private keys can either be a one line string, or
the proper 64-character per-line versions with -----BEGIN----- and 
-----END-----. The library will handle either.

