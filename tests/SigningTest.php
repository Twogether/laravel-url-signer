<?php
namespace Tests;

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
    }
    
    public function test_a_url_with_just_host_works()
    {
        $url = 'https://example.com';

        $signed = URLSigner::sign($url,$this->privateKey);

        $this->assertEquals($url,
            substr($signed,0,strlen($url))
        );
    }

    public function test_parameters_are_present()
    {
        $signed = URLSigner::sign('https://example.com',$this->privateKey);

        parse_str(parse_url($signed)['query'],$params);

        $this->assertArrayHasKey('ac_nc',$params);
        $this->assertArrayHasKey('ac_sg',$params);
        $this->assertArrayHasKey('ac_ts',$params);

    }

    public function test_a_url_with_path_works()
    {
        $url = 'https://example.com/foo';

        $signed = URLSigner::sign($url,$this->privateKey);

        $this->assertEquals($url,
            substr($signed,0,strlen($url))
        );
    }

    public function test_query_strings_are_not_doubled_up()
    {
        $root = 'https://example.com/foo';
        $url = $root.'?xyz=abc';

        $signed = URLSigner::sign($url,$this->privateKey);

        $this->assertEquals($root,
            substr($signed,0,strlen($root))
        );

        $this->assertEquals(1,substr_count($signed,"?"));
    }

    public function test_query_parameters_are_preserved()
    {
        $url = 'https://example.com/foo?xyz=abc';
        $signed = URLSigner::sign($url,$this->privateKey);

        parse_str(parse_url($signed)['query'],$params);

        $this->assertEquals('abc',$params['xyz']);
    }

    /**
     * @group validation
     */
    public function test_a_signature_can_be_validated()
    {
        $signed = URLSigner::sign('https://example.com',$this->privateKey);

        $this->assertTrue(URLSigner::validate($signed,$this->publicKey)->isValid());
    }

    /**
     * @group validation
     */
    public function test_parameters_cannot_be_missing()
    {
        $validated = URLSigner::validate('https://example.com',$this->publicKey);
        $this->assertFalse($validated->isValid());
        $this->assertCount(4,$validated->errors());
    }

    /**
     * @group validation
     */
    public function test_an_individual_parameter_cannot_be_missing()
    {
        $validated = URLSigner::validate('https://example.com?ac_sg=1&ac_ts=444',$this->publicKey);
        $this->assertFalse($validated->isValid());
        $this->assertCount(2,$validated->errors());
        $this->assertArrayHasKey('ac_nc',$validated->errors());
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
        $url = $this->tamperWithSignedUrl(['ac_ts' => time()+400]);
        $this->assertFalse(URLSigner::validate($url,$this->publicKey)->isValid());
    }

    /**
     * @group validation
     */
    public function test_a_timestamp_in_the_far_past_fails()
    {
        $url = $this->tamperWithSignedUrl(['ac_ts' => time()-400]);
        $result = URLSigner::validate($url,$this->publicKey);
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('ac_ts',$result->errors());
    }

    /**
     * @group validation
     */
    public function test_a_timestamp_slightly_off_is_okay()
    {
        $url = URLSigner::sign('https://example.com',$this->privateKey);
        sleep(1);
        $this->assertTrue(URLSigner::validate($url,$this->publicKey)->isValid());
    }

    /**
     * @group validation
     */
    public function test_a_nonce_cannot_be_reused()
    {
        $url = URLSigner::sign('https://example.com',$this->privateKey);

        $this->assertTrue(URLSigner::validate($url,$this->publicKey)->isValid());
        $this->assertFalse(URLSigner::validate($url,$this->publicKey)->isValid());
    }

    /**
     * @group validation
     */
    public function test_a_tampered_signature_causes_a_failure()
    {
        $url = $this->tamperWithSignedUrl(['ac_sg' => 'tampered']);
        $result = URLSigner::validate($url,$this->publicKey);
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('ac_sg',$result->errors());
    }


    /**
     * @group validation
     */
    public function test_tampering_with_the_timestamp_causes_a_failure()
    {
        $url = $this->tamperWithSignedUrl(['ac_ts' => time()-2]);
        $this->assertFalse(URLSigner::validate($url,$this->publicKey)->isValid());
    }


    /**
     * @group validation
     */
    public function test_any_other_tampering_causes_a_failure()
    {
        $url = $this->tamperWithSignedUrl(['foo' => 'bar']);
        $this->assertFalse(URLSigner::validate($url,$this->publicKey)->isValid());
    }





    /**
     * @param array $tamper
     * @return string
     */
    private function tamperWithSignedUrl(array $tamper)
    {
        $base = 'https://example.com';
        $signed = URLSigner::sign($base,$this->privateKey);
        parse_str(parse_url($signed)['query'],$params);

        $params = array_merge($params,$tamper);
        return $base."?".http_build_query($params);

    }

}