<?php
/**
 * @file Uberspace.php
 */

namespace clever_systems\mmm2\ServerType;


use clever_systems\mmm2\ServerInterface;

class Uberspace extends ServerInterface {
  /** @var string */
  protected $host;
  /** @var string */
  protected $user;

  /**
   * Uberspace constructor.
   * @param string $host
   * @param string $user
   */
  public function __construct($host, $user) {
    $this->host = $host;
    $this->user = $user;
  }


}