<?php
namespace App\Service;

use Stripe\StripeClient;

class StripeFactory {
  public function __construct(private string $secretKey) {}
  public function client(): StripeClient { return new StripeClient($this->secretKey); }
}