<?php
namespace Pagaris;

class SignatureTest extends TestCase
{
  public function testInitializingClassWithError()
  {
    $this->expectException(\ArgumentCountError::class);
    new Signature();
  }

  public function testgetData()
  {
    $signature = new Signature("a", "b", "c", "d");
    $this->assertEquals("a-b-c-d", $signature->getData());
  }

  # See https://www.freeformatter.com/hmac-generator.html
  public function testGetValue($value='')
  {
    Pagaris::setPrivateKey("z9x8c7v6b5n4m3");
    $signature = new Signature("a", "b", "c", "d");
    $expected = "bbdd58807fafe7b30767d278c6e5a0c7b6419820d8a16e12dfd9d4a2c271336f";
    $this->assertEquals($expected, $signature->getValue());

    $signature = new Signature("q", "w", "e", "r");
    $expected = "ad75798d1f30d6e1f71c612f50b5a3eee8df7fc891a71225388b0a0cc076aa7f";
    $this->assertEquals($expected, $signature->getValue());

    Pagaris::setPrivateKey("7fc891a71225388b0a0");
    $signature = new Signature(1, 2, 3, 4);
    $expected = "6751517254bc1d4013b249a586e5467889afc85c4ade988dd87307d20d4b2b51";
    $this->assertEquals($expected, $signature->getValue());

    $signature = new Signature(1, 2, 3, null);
    $expected = "987d90fa0e1d3628c1667e253579fd3dd984b3af3979e7aee383c057cea3c85c";
    $this->assertEquals($expected, $signature->getValue());

    // Equivalent to calling `time()` at 2019-01-01T14:30:10Z
    Pagaris::setPrivateKey("PRIVATE_KEY");
    $timestamp = mktime(14, 30, 10, 1, 1, 2019);
    $this->assertEquals(1546353010, $timestamp);
    $method = "GET";
    $path = "/api/v1/orders";
    $body = null;
    $signature = new Signature($timestamp, $method, $path, $body);
    $expected = "fc73d481da919b190e42b54c69e5ebd993b010bd6332c6a5cb78d07158d7939a";
    $this->assertEquals($expected, $signature->getValue());

    $method = "POST";
    $body = '{"order":{"amount":1500.43}}';
    $signature = new Signature($timestamp, $method, $path, $body);
    $expected = "9d068ccabdf4a418adacfc4a151cddaa8f29dfd7222dd8ccd4b429ca396d7ab5";
    $this->assertEquals($expected, $signature->getValue());
  }
}
