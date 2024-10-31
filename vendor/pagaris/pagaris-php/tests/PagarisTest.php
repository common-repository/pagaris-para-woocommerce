<?php
namespace Pagaris;

class PagarisTest extends TestCase
{
  public function testAbstractClass()
  {
    $this->expectException(\Error::class);
    new Pagaris();
  }

  public function testsetApplicationId()
  {
    Pagaris::setApplicationId("abcd1234");
    $this->assertEquals("abcd1234", Pagaris::getApplicationId());

    // Can also be gotten and set directly on the public variable
    Pagaris::$applicationId = "aaaaa";
    $this->assertEquals("aaaaa", Pagaris::getApplicationId());
    $this->assertEquals("aaaaa", Pagaris::$applicationId);
  }

  public function testsetPrivateKey()
  {
    Pagaris::setPrivateKey("1234abcd");
    $this->assertEquals("1234abcd", Pagaris::getPrivateKey());

    // Can also be gotten and set directly on the public variable
    Pagaris::$privateKey = "aaaaa";
    $this->assertEquals("aaaaa", Pagaris::getPrivateKey());
    $this->assertEquals("aaaaa", Pagaris::$privateKey);
  }

  public function testVerifyWebhookSignatuerr()
  {
    $valid = Pagaris::verifyWebhookSignature([
      'signature' => 'a'
    ]);
    $this->assertFalse($valid);

    $valid = Pagaris::verifyWebhookSignature([
      'signature' => '09974201d545b23e3b17b9577880e2cf9101085dc61e1d693469e845f3c2c41a',
      'timestamp' => mktime(14, 30, 10, 1, 1, 2019),
      'path' => '/pagaris_webhooks'
    ]);
    $this->assertTrue($valid);
  }
}
