<?php namespace Pagaris;

abstract class Client
{
  const DOMAIN = "https://pagaris.com";
  const API_URL_PORTION = "/api/v1/";
  const BASE_URL = self::DOMAIN . self::API_URL_PORTION;

  public static function get($path, $body = null)
  {
    return self::request("GET", $path, $body);
  }

  public static function post($path, $body = null)
  {
    return self::request("POST", $path, $body);
  }

  public static function put($path, $body = null)
  {
    return self::request("PUT", $path, $body);
  }

  protected static function request($method, $path, $body)
  {
    $client = new \GuzzleHttp\Client();
    $headers = [
      'Authorization' => self::authorizationHeader($method, $path, $body),
      'Content-Type' => 'application/json'
    ];

    $method_to_call = strtolower($method);
    $response = $client->$method_to_call(self::BASE_URL . $path, [
      'headers' => $headers,
      'body' => $body
    ]);
    return json_decode($response->getBody()->getContents(), true);
  }

  protected static function authorizationHeader($method, $path, $body)
  {
    $timestamp = time();
    $path = self::API_URL_PORTION . $path; // e.g. '/api/v1/a' if `$path == 'a'`
    $signature = new Signature($timestamp, $method, $path, $body);
    $signature = $signature->getValue();
    return "Pagaris " . Pagaris::$applicationId . ":" . $timestamp . ":" .
           $signature;
  }
}
