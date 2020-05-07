<?php
namespace Twogether\LaravelURLSignerTests;

use Twogether\LaravelURLSigner\Exceptions\InvalidSignedUrl;
use Twogether\LaravelURLSigner\Exceptions\InvalidUrl;
use Twogether\LaravelURLSigner\Exceptions\PrivateKeyNotFound;
use Twogether\LaravelURLSigner\Exceptions\PublicKeyNotFound;
use Twogether\LaravelURLSigner\KeyProviders\ConfigKeyProvider;
use Twogether\LaravelURLSigner\CacheBrokers\LaravelCacheBroker;
use Twogether\LaravelURLSigner\SignedUrlFactory;

class SigningTest
    extends TestCase
{
    private $privateKey;
    private $publicKey;
    private $factory;
    
    public function setUp(): void
    {
        parent::setUp();
        $this->privateKey = $this->getResource('dummy_private_key.txt');
        $this->publicKey = $this->getResource('dummy_public_key.txt');

        config(['signed_urls.keys.default.private' => $this->privateKey]);
        config(['signed_urls.keys.default.public' => $this->publicKey]);

        $this->factory = new SignedUrlFactory(
            'test',
            new LaravelCacheBroker(),
            new ConfigKeyProvider()
        );
    }

    /**
     * @group v2
     */
    public function test_a_missing_private_key_triggers_an_exception()
    {
        config(['signed_urls.keys.default.private' => '']);
        $this->expectException(PrivateKeyNotFound::class);
        $this->factory->sign('https://example.com');
    }

    /**
     * @group v2
     */
    public function test_a_missing_public_key_triggers_an_exception()
    {
        config(['signed_urls.keys.default.public' => '']);
        $this->expectException(PublicKeyNotFound::class);
        $signed = $this->factory->sign('https://example.com');
        $this->factory->validate($signed);
    }

    /**
     * @group v2
     */
    public function test_an_alternative_key_can_be_used()
    {
        $this->expectNotToPerformAssertions();
        config(['signed_urls.keys.different.private' => $this->privateKey]);
        $this->factory->sign('https://example.com','different');
    }


    public function test_an_alternative_key_can_be_used_for_validation()
    {
        config(['signed_urls.keys.different.public' => $this->publicKey]);
        $signed = $this->factory->sign('https://example.com');
        $this->assertTrue($this->factory->validate($signed,'different'));
    }

    /**
     * @group v2
     */
    public function test_a_non_existent_key_triggers_exception()
    {
        $this->expectException(PrivateKeyNotFound::class);
        $this->factory->sign('https://example.com','not_implemented');
    }

    /**
     * @group v2
     */
    public function test_passing_nothing_triggers_an_exception()
    {
        $this->expectException(PrivateKeyNotFound::class);
        $this->factory->sign('https://example.com','');
    }

    public function test_validating_with_nothing_triggers_an_exception()
    {
        $this->expectException(InvalidSignedUrl::class);
        $this->factory->validate('https://example.com','','');
    }

    /**
     * @group v2
     */
    public function test_a_url_with_just_host_works()
    {
        $url = 'https://example.com';

        $signed = $this->factory->sign($url);

        $this->assertEquals($url,
            substr($signed,0,strlen($url))
        );
    }

    /**
     * @group v2
     */
    public function test_an_invalid_url_throws_an_exception()
    {
        $this->expectException(InvalidUrl::class);
        $this->factory->sign("/");
    }

    /**
     * @group v2
     */
    public function test_parameters_are_present()
    {
        $signed = $this->factory->sign('https://example.com');

        parse_str(parse_url($signed)['query'],$params);

        $this->assertArrayHasKey('ac_nc',$params);
        $this->assertArrayHasKey('ac_sg',$params);
        $this->assertArrayHasKey('ac_ts',$params);
        $this->assertArrayHasKey('ac_xp',$params);

    }

    /**
     * @group v2
     */
    public function test_a_url_with_path_works()
    {
        $url = 'https://example.com/foo';

        $signed = $this->factory->sign($url);

        $this->assertEquals($url,
            substr($signed,0,strlen($url))
        );
    }

    /**
     * @group v2
     */
    public function test_query_strings_are_not_doubled_up()
    {
        $root = 'https://example.com/foo';
        $url = $root.'?xyz=abc';

        $signed = $this->factory->sign($url);

        $this->assertEquals($root,
            substr($signed,0,strlen($root))
        );

        $this->assertEquals(1,substr_count($signed,"?"));
    }

    /**
     * @group v2
     */
    public function test_query_parameters_are_preserved()
    {
        $url = 'https://example.com/foo?xyz=abc';
        $signed = $this->factory->sign($url);

        parse_str(parse_url($signed)['query'],$params);

        $this->assertEquals('abc',$params['xyz']);
    }

    /**
     * @group validation
     */
    public function test_a_signature_can_be_validated()
    {
        $signed = $this->factory->sign('https://example.com');

        $this->assertTrue($this->factory->validate($signed));
    }

    /**
     * @group validation
     */
    public function test_all_parameters_cannot_be_missing()
    {
        $this->expectException(InvalidSignedUrl::class);
        $this->factory->validate('https://example.com');
        $this->assertCount(4,$this->getExpectedException()->errors());
    }

    /**
     * @group validation
     */
    public function test_some_parameters_cannot_be_missing()
    {
        $this->expectException(InvalidSignedUrl::class);

        $this->factory->validate('https://example.com?ac_sg=1&ac_ts=444');

        $this->assertCount(2,$this->getExpectedException()->errors());
        $this->assertArrayHasKey('ac_nc',$this->getExpectedException()->errors());
    }

    /**
     * @group validation
     */
    public function test_tampering_works()
    {
        $url = $this->tamperWithSignedUrl(['ac_sg' => 'tampered_sig']);
        $this->assertEquals(1,substr_count($url,'ac_sg=tampered_sig'));
    }

    /**
     * @group validation
     */
    public function test_a_timestamp_in_the_far_future_fails()
    {
        $this->expectException(InvalidSignedUrl::class);
        $url = $this->tamperWithSignedUrl(['ac_ts' => time()+400]);
        $this->factory->validate($url);
    }

    /**
     * @group validation
     */
    public function test_a_timestamp_in_the_far_past_fails()
    {
        $url = $this->tamperWithSignedUrl(['ac_ts' => time()-400]);
        $this->expectException(InvalidSignedUrl::class);

        $this->factory->validate($url);

        $this->assertArrayHasKey('ac_ts',$this->getExpectedException()->errors());
    }

    /**
     * @group validation
     */
    public function test_a_timestamp_slightly_off_is_okay()
    {
        $url = $this->factory->sign('https://example.com');
        sleep(1);
        $this->assertTrue($this->factory->validate($url));
    }

    /**
     * @group validation
     */
    public function test_a_signed_url_cannot_be_reused()
    {
        $url = $this->factory->sign('https://example.com');

        $this->assertTrue($this->factory->validate($url));

        $this->expectException(InvalidSignedUrl::class);
        $this->factory->validate($url);
    }

    /**
     * @group validation
     */
    public function test_a_tampered_signature_causes_a_failure()
    {
        $url = $this->tamperWithSignedUrl(['ac_sg' => 'tampered']);
        $this->expectException(InvalidSignedUrl::class);

        $this->factory->validate($url);

        $this->assertArrayHasKey('ac_sg',$this->getExpectedException()->errors());
    }


    /**
     * @group validation
     */
    public function test_tampering_with_the_timestamp_causes_a_failure()
    {
        $this->expectException(InvalidSignedUrl::class);

        $url = $this->tamperWithSignedUrl(['ac_ts' => time()-2]);
        $this->factory->validate($url);
    }


    /**
     * @group validation
     */
    public function test_any_other_tampering_causes_a_failure()
    {
        $this->expectException(InvalidSignedUrl::class);

        $url = $this->tamperWithSignedUrl(['foo' => 'bar']);
        $this->factory->validate($url);
    }

    /**
     * @group expiry
     */
    public function test_a_url_within_expiry_is_valid()
    {
        $this->expectNotToPerformAssertions();


        $url = $this->factory->make('https://example.com')
            ->withExpiry(time()+300)
            ->get();

        try {
            $this->factory->validate($url);
        } catch(InvalidSignedUrl $e) {
            dd($e->errors());
            throw $e;
        }
    }

    /**
     * @group expiry
     */
    public function test_an_expired_url_is_invalid()
    {
        $this->expectException(InvalidSignedUrl::class);

        $url = $this->factory->make('https://example.com')->withExpiry(time()-300)->get();

        $this->factory->validate($url);
    }


    /**
     * @group parameters
     */
    public function test_a_parameter_can_be_added()
    {
        $url = $this->factory->make('https://example.com?foo=bar')->withParameter('bar','foo')->get();

        $this->factory->validate($url);

        $parts = parse_url($url);
        parse_str($parts['query'],$query);

        $this->assertEquals('bar',$query['foo']);
        $this->assertEquals('foo',$query['bar']);

    }


    /**
     * @group url
     */
    public function test_a_url_cannot_be_changed()
    {
        $this->expectException(InvalidSignedUrl::class);

        $url = $this->factory->make('https://example.com');

        $url = str_replace('example.com','different-example.com',$url);

        $this->factory->validate($url);

    }


    /**
     * @group url
     */
    public function test_a_scheme_cannot_be_changed()
    {
        $this->expectException(InvalidSignedUrl::class);

        $url = $this->factory->make('https://example.com');

        $url = 'http'.substr($url,5);

        $this->factory->validate($url);

    }


    /**
     * @group url
     */
    public function test_a_path_cannot_be_changed()
    {
        $this->expectException(InvalidSignedUrl::class);

        $url = $this->factory->make('https://example.com/path-to/my-folder');

        $url = str_replace('/path-to/my-folder','/different-path',$url);

        $this->factory->validate($url);

    }



    /**
     * @param array $tamper
     * @return string
     */
    private function tamperWithSignedUrl(array $tamper)
    {
        $base = 'https://example.com';
        $signed = $this->factory->sign($base);
        parse_str(parse_url($signed)['query'],$params);

        $params = array_merge($params,$tamper);
        return $base."?".http_build_query($params);

    }

}