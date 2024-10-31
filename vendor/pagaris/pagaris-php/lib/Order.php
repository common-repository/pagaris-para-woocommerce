<?php namespace Pagaris;

class Order
{
  protected $id;
  protected $amount;
  protected $status;
  protected $metadata;
  protected $products;
  protected $redirect_url;
  protected $created_at;
  protected $updated_at;
  protected $url;

  const PROPERTIES_FOR_WRITING = ['amount', 'metadata', 'products',
    'redirect_url'];
  const PROPERTIES_FOR_SETTING = ['id', 'amount', 'status', 'metadata',
    'products', 'redirect_url', 'created_at', 'updated_at', 'url'];

  // Make protected properties readable.
  function __get($name)
  {
    return $this->$name ?? null;
  }

  protected function __construct($amount, $metadata, $products, $redirect_url)
  {
    $this->amount = $amount;
    $this->metadata = $metadata;
    $this->products = $products;
    $this->redirect_url = $redirect_url;
  }

  protected function body()
  {
    $order_body = [];
    foreach (self::PROPERTIES_FOR_WRITING as $prop) {
      $order_body[$prop] = $this->$prop;
    }

    return json_encode(['order' => $order_body]);
  }

  protected function updateFromResponse($response)
  {
    foreach (self::PROPERTIES_FOR_SETTING as $prop) {
      $this->$prop = $response[$prop];
    }

    // Amount is converted to `Float`
    $this->amount = floatval($this->amount); // convert string amount to float

    // ISO8601 timestamps are converted to `DateTime`
    $this->created_at = new \DateTime($this->created_at);
    $this->updated_at = new \DateTime($this->updated_at);

    return $this;
  }

  public function confirm()
  {
    $response = Client::put("orders/{$this->id}/confirm")['order'];
    return $this->updateFromResponse($response);
  }

  public function cancel()
  {
    $response = Client::put("orders/{$this->id}/cancel")['order'];
    return $this->updateFromResponse($response);
  }

  public static function create($params)
  {
    $amount = $params['amount'];
    $metadata = $params['metadata'] ?? null;
    $products = $params['products'] ?? null;
    $redirect_url = $params['redirect_url'] ?? null;

    $order = new Order($amount, $metadata, $products, $redirect_url);
    $response = Client::post('orders', $order->body())['order'];
    return $order->updateFromResponse($response);
  }

  public static function all()
  {
    $orders = [];
    $response = Client::get('orders')['orders'];
    foreach ($response as $responseOrder) {
      $order = new Order(null, null, null, null);
      $orders[] = $order->updateFromResponse($responseOrder);
    }
    return $orders;
  }

  public static function get($id)
  {
    $order = new Order(null, null, null, null);
    $response = Client::get("orders/{$id}", $order->body())['order'];
    return $order->updateFromResponse($response);
  }
}
