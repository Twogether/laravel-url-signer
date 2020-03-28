<?php
namespace Twogether\LaravelURLSignerTests;

use Twogether\LaravelURLSigner\Exceptions\InvalidSignedUrl;
use Twogether\LaravelURLSigner\Exceptions\PrivateKeyNotFound;
use Twogether\LaravelURLSigner\Exceptions\PublicKeyNotFound;
use Twogether\LaravelURLSigner\SignedUrl;
use Twogether\LaravelURLSigner\URLSigner;

class SigningTest
    extends TestCase
{
    private $privateKey;
    private $publicKey;
    
    public function setUp(): void
    {
        parent::setUp();
        $this->privateKey = $this->getResource('dummy_private_key.txt');
        $this->publicKey = $this->getResource('dummy_public_key.txt');

        config(['signed_urls.private_keys.default' => $this->privateKey]);
        config(['signed_urls.public_keys.default' => $this->publicKey]);
    }

    public function test_a_missing_private_key_triggers_an_exception()
    {
        config(['signed_urls.private_keys.default' => '']);

        $this->expectException(PrivateKeyNotFound::class);

        URLSigner::sign('https://example.com');
    }

    public function test_a_missing_public_key_triggers_an_exception()
    {
        config(['signed_urls.public_keys.default' => '']);
        $this->expectException(PublicKeyNotFound::class);

        URLSigner::validate('https://example.com');
    }

    public function test_an_alternative_config_can_be_used()
    {
        $this->expectNotToPerformAssertions();
        config(['signed_urls.private_keys.different' => $this->privateKey]);
        URLSigner::sign('https://example.com','different');
    }

    public function test_an_alternative_config_can_be_used_for_public_keys()
    {
        config(['signed_urls.public_keys.different' => $this->publicKey]);
        $signed = URLSigner::sign('https://example.com');
        $this->assertTrue(URLSigner::validate($signed,'different'));
    }

    public function test_a_non_existent_config_triggers_exception()
    {
        $this->expectException(PrivateKeyNotFound::class);
        URLSigner::sign('https://example.com','not_implemented');
    }

    public function test_passing_nothing_triggers_an_exception()
    {
        $this->expectException(PrivateKeyNotFound::class);
        URLSigner::sign('https://example.com','','');
    }

    public function test_validating_with_nothing_triggers_an_exception()
    {
        $this->expectException(PublicKeyNotFound::class);
        URLSigner::validate('https://example.com','','');
    }

    
    public function test_a_url_with_just_host_works()
    {
        $url = 'https://example.com';

        $signed = URLSigner::sign($url);

        $this->assertEquals($url,
            substr($signed,0,strlen($url))
        );
    }

    public function test_only_a_path_works()
    {
        $url = '/';

        $signed = URLSigner::sign($url);

        $this->assertEquals($url,
            substr($signed,0,strlen($url))
        );
    }


    public function test_parameters_are_present()
    {
        $signed = URLSigner::sign('https://example.com');

        parse_str(parse_url($signed)['query'],$params);

        $this->assertArrayHasKey('ac_nc',$params);
        $this->assertArrayHasKey('ac_sg',$params);
        $this->assertArrayHasKey('ac_ts',$params);

    }

    public function test_a_url_with_path_works()
    {
        $url = 'https://example.com/foo';

        $signed = URLSigner::sign($url);

        $this->assertEquals($url,
            substr($signed,0,strlen($url))
        );
    }

    public function test_query_strings_are_not_doubled_up()
    {
        $root = 'https://example.com/foo';
        $url = $root.'?xyz=abc';

        $signed = URLSigner::sign($url);

        $this->assertEquals($root,
            substr($signed,0,strlen($root))
        );

        $this->assertEquals(1,substr_count($signed,"?"));
    }

    public function test_query_parameters_are_preserved()
    {
        $url = 'https://example.com/foo?xyz=abc';
        $signed = URLSigner::sign($url);

        parse_str(parse_url($signed)['query'],$params);

        $this->assertEquals('abc',$params['xyz']);
    }

    /**
     * @group validation
     */
    public function test_a_signature_can_be_validated()
    {
        $signed = URLSigner::sign('https://example.com');

        $this->assertTrue(URLSigner::validate($signed));
    }

    /**
     * @group validation
     */
    public function test_all_parameters_cannot_be_missing()
    {
        $this->expectException(InvalidSignedUrl::class);
        URLSigner::validate('https://example.com');
        $this->assertCount(4,$this->getExpectedException()->errors());
    }

    /**
     * @group validation
     */
    public function test_some_parameters_cannot_be_missing()
    {
        $this->expectException(InvalidSignedUrl::class);

        URLSigner::validate('https://example.com?ac_sg=1&ac_ts=444');

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
        URLSigner::validate($url);
    }

    /**
     * @group validation
     */
    public function test_a_timestamp_in_the_far_past_fails()
    {
        $url = $this->tamperWithSignedUrl(['ac_ts' => time()-400]);
        $this->expectException(InvalidSignedUrl::class);

        URLSigner::validate($url);

        $this->assertArrayHasKey('ac_ts',$this->getExpectedException()->errors());
    }

    /**
     * @group validation
     */
    public function test_a_timestamp_slightly_off_is_okay()
    {
        $url = URLSigner::sign('https://example.com');
        sleep(1);
        $this->assertTrue(URLSigner::validate($url));
    }

    /**
     * @group validation
     */
    public function test_a_signed_url_cannot_be_reused()
    {
        $url = URLSigner::sign('https://example.com');

        $this->assertTrue(URLSigner::validate($url));

        $this->expectException(InvalidSignedUrl::class);
        URLSigner::validate($url);
    }

    /**
     * @group validation
     */
    public function test_a_tampered_signature_causes_a_failure()
    {
        $url = $this->tamperWithSignedUrl(['ac_sg' => 'tampered']);
        $this->expectException(InvalidSignedUrl::class);

        URLSigner::validate($url);

        $this->assertArrayHasKey('ac_sg',$this->getExpectedException()->errors());
    }


    /**
     * @group validation
     */
    public function test_tampering_with_the_timestamp_causes_a_failure()
    {
        $this->expectException(InvalidSignedUrl::class);

        $url = $this->tamperWithSignedUrl(['ac_ts' => time()-2]);
        URLSigner::validate($url);
    }


    /**
     * @group validation
     */
    public function test_any_other_tampering_causes_a_failure()
    {
        $this->expectException(InvalidSignedUrl::class);

        $url = $this->tamperWithSignedUrl(['foo' => 'bar']);
        URLSigner::validate($url);
    }

    /**
     * @group expiry
     */
    public function test_a_url_within_expiry_is_valid()
    {
        $this->expectNotToPerformAssertions();

        $url = (new SignedUrl('https://example.com'))
            ->withExpiry(time()+300);

        URLSigner::validate($url);
    }

    /**
     * @group expiry
     */
    public function test_an_expired_url_is_invalid()
    {
        $this->expectException(InvalidSignedUrl::class);

        $url = (new SignedUrl('https://example.com'))
            ->withExpiry(time()-300);

        URLSigner::validate($url);
    }


    /**
     * @param array $tamper
     * @return string
     */
    private function tamperWithSignedUrl(array $tamper)
    {
        $base = 'https://example.com';
        $signed = URLSigner::sign($base);
        parse_str(parse_url($signed)['query'],$params);

        $params = array_merge($params,$tamper);
        return $base."?".http_build_query($params);

    }

}