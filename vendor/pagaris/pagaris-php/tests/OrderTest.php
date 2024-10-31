<?php
namespace Pagaris;

class OrderTest extends TestCase
{
  public function testcreateWithError()
  {
    $this->expectException(\ArgumentCountError::class);
    Order::create();
  }

  public function testAbstractClass($value='')
  {
    $this->expectException(\Error::class);
    new Order();
  }

  public function testcreate()
  {
    \VCR\VCR::insertCassette('order_test_create');
    $order = Order::create([
      'amount' => 1234.56,
      'metadata' => ['a' => 'b', 'c' => ['d' => 'e']],
      'products' => [['a' => 'b'], ['c' => 'd']],
      'redirect_url' => 'http://google.com'
    ]);
    \VCR\VCR::eject();

    $this->assertNotNull($order);
    $this->assertNotNull($order->id);
    $this->assertNotNull($order->url);
    $this->assertEquals(1234.56, $order->amount);
    $this->assertInternalType('float', $order->amount);
    $this->assertNotNull($order->created_at);
    $this->assertInstanceOf('DateTime', $order->created_at);
    $this->assertNotNull($order->updated_at);
    $this->assertInstanceOf('DateTime', $order->updated_at);
    $this->assertEquals('b', $order->metadata['a']);
    $this->assertEquals('e', $order->metadata['c']['d']);
    $this->assertEquals('http://google.com', $order->redirect_url);
  }

  public function testOnlyReadableProperties()
  {
    \VCR\VCR::insertCassette('order_test_only_readable_properties');
    $order = Order::create([
      'amount' => 1234
    ]);
    \VCR\VCR::eject();

    $this->assertNotNull($order);
    $this->assertEquals(1234, $order->amount);

    $this->expectException(\Error::class);
    $order->amount = 543;
  }

  public function testGet()
  {
    \VCR\VCR::insertCassette('order_test_get');
    $order = Order::get('d2eaabec-2aa7-4c8f-bdcc-1c26c9a1c4a5');
    \VCR\VCR::eject();

    $this->assertNotNull($order);
    $this->assertEquals('d2eaabec-2aa7-4c8f-bdcc-1c26c9a1c4a5', $order->id);
    $expected = 'http://localhost:3000/orders/d2eaabec-2aa7-4c8f-bdcc-1c26c9a1c4a5/lr';
    $this->assertEquals($expected, $order->url);
    $this->assertEquals(12345.0, $order->amount);
    $this->assertNotNull($order->created_at);
    $this->assertNotNull($order->updated_at);
    $this->assertNull($order->metadata);
    $this->assertNull($order->products);
    $this->assertNull($order->redirect_url);
  }

  public function testAll()
  {
    \VCR\VCR::insertCassette('order_test_all');
    $orders = Order::all();
    \VCR\VCR::eject();

    // $orders is an array of Order objects.
    $this->assertNotNull($orders);
    $order = array_filter($orders, function ($element) {
      return $element->id == 'd2eaabec-2aa7-4c8f-bdcc-1c26c9a1c4a5';
    })[0];
    $this->assertNotNull($order);
    $this->assertEquals(12345.0, $order->amount);
  }

  public function testConfirm()
  {
    \VCR\VCR::insertCassette('order_test_confirm');
    // Get an approved Order
    $order = Order::get('e44c8acc-3750-439a-ace0-85f70c7dc4af');
    $this->assertEquals('approved', $order->status);

    // Confirm it:
    $order->confirm();
    $this->assertEquals('confirmed', $order->status);
    \VCR\VCR::eject();
  }

  public function testCancel()
  {
    \VCR\VCR::insertCassette('order_test_cancel');
    // First, create an Order
    $order = Order::create([
      'amount' => 3876.32
    ]);
    $this->assertEquals('created', $order->status);

    // Cancel it:
    $order->cancel();
    $this->assertEquals('cancelled', $order->status);
    \VCR\VCR::eject();
  }
}
