<?php namespace Pagaris;

require_once 'Signature.php';

abstract class Pagaris{

  public static $applicationId;
  public static $privateKey;

  public static function getApplicationId()
  {
    return self::$applicationId;
  }

  public static function setApplicationId($applicationId)
  {
    self::$applicationId = $applicationId;
  }

  public static function getPrivateKey()
  {
    return self::$privateKey;
  }

  public static function setPrivateKey($privateKey)
  {
    self::$privateKey = $privateKey;
  }

  /**
   * options['signature'] must be equal to an expected Signature created with:
   *   - options['body']
   *   - options['timestamp']
   *   - options['path']
   */
  public static function verifyWebhookSignature($options)
  {
    $signature = $options['signature'];
    $method = 'POST';

    $body = $options['body'] ?? null;
    $timestamp = $options['timestamp'] ?? null;
    $path = $options['path'] ?? null;

    $expectedSignature = new Signature($timestamp, $method, $path, $body);

    return hash_equals($signature, $expectedSignature->getValue());
  }
}
