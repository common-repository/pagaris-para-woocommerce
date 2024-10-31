<?php namespace Pagaris;

class Signature
{
  private $data;
  private $value;

  public function __construct($timestamp, $method, $path, $body)
  {
    $this->data = $timestamp . "-" . $method . "-" . $path . "-" . $body;
    $this->value = hash_hmac("sha256", $this->data, Pagaris::getPrivateKey());
  }

  public function getData()
  {
    return $this->data;
  }

  public function getValue()
  {
    return $this->value;
  }
}
