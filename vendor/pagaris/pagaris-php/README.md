# Overview

This is the official PHP library for the [Pagaris API](https://pagaris.com/docs?php). Pagaris is the easiest and cheapest way to let the customers of your e-commerce to have their purchases financed, even without having a credit card. Merchants receive their money upfront and instantly.

Pagaris is currently available in Mexico only.

# Installation

`composer require pagaris/pagaris-php`

# Usage

```php
<?php

require 'vendor/autoload.php';

// Never store your application id and private key in cleartext in your
// repository. We recommend using environment variables.
Pagaris\Pagaris::setApplicationId(getenv('PAGARIS_APPLICATION_ID'));
Pagaris\Pagaris::setPrivateKey(getenv('PAGARIS_PRIVATE_KEY'));

// Create an Order
$order = Pagaris\Order::create([
  'amount' => 1439,
  'metadata' => ['special_test_case' => 'rejected']
]);

// Get Orders
$orders = Pagaris\Order::all();

// Get an Order
$order = Pagaris\Order::get('id');
$order->status # 'created',...

// Confirm an Order
$order->confirm();

// Cancel an Order
$order->cancel();

// Verify a webhook (returns a boolean)
$valid = Pagaris\Pagaris::verifyWebhookSignature([
  // Received signature on `Authorization` header
  'signature' => '09974201d545b23e3b17b9577880e2cf9101085dc61e1d693469e845f3c2c41a',
  // Received timestamp on `Authorization` header
  'timestamp' => '1546353010',
  // Path in which you received the webhook
  'path' => '/pagaris_webhooks',
  // Body of the webhook
  'body' => '{"event":{"type":"order.status_update"},"payload":{}}'
]);
```

---

# Development

## Install dependencies

`composer i`

## Run test suite

`./vendor/bin/phpunit`
