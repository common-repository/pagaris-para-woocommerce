<?php
namespace Pagaris;

class ClientTest extends TestCase
{
  public function testbaseUrl()
  {
    $this->assertEquals("https://pagaris.com/api/v1/", Client::BASE_URL);
  }

  public function testThrownErrors()
  {
    $this->expectException(\GuzzleHttp\Exception\TransferException::class);
    \VCR\VCR::insertCassette('client_test_thrown_errors');
    Client::get('non-existing-path');
    \VCR\VCR::eject();
  }

  public function testGet()
  {
    \VCR\VCR::insertCassette('client_test_get');

    $response = Client::get('orders');
    $this->assertNotNull($response['orders']);

    $response = Client::get('orders/d2eaabec-2aa7-4c8f-bdcc-1c26c9a1c4a5');
    $this->assertNotNull($response['order']);

    \VCR\VCR::eject();
  }

  public function testPost()
  {
    \VCR\VCR::insertCassette('client_test_post');

    $response = Client::post('orders', '{"order":{"amount": 543}}');
    $this->assertNotNull($response['order']);

    \VCR\VCR::eject();
  }
}
