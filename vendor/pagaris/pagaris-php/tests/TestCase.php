<?php
namespace Pagaris;

require_once 'lib/Pagaris.php';

\VCR\VCR::configure()->setCassettePath('tests/fixtures');
\VCR\VCR::configure()
    ->enableRequestMatchers(array('method', 'url', 'query_string', 'body'));
\VCR\VCR::turnOn();

/**
 * Base class for Pagaris test cases.
 */
class TestCase extends \PHPUnit\Framework\TestCase
{
  protected function setUp(): void
  {
    Pagaris::$applicationId = "3f482d1f-ef7a-487b-a1f2-22fd69595159";
    Pagaris::$privateKey = "qELZaxByUVdg2qc4BkCfgSmQ";
  }
}
