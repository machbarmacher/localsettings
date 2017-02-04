<?php
/**
 * @file Uberspace.php
 */

namespace clever_systems\mmm_builder\ServerType;


use clever_systems\mmm_builder\ServerBase;
use clever_systems\mmm_builder\ServerInterface;

class UberspaceServer extends ServerBase implements ServerInterface {
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
    $this->host = $host . '.uberspace.de';
    $this->user = $user;
  }

  public function getDefaultDocroot() {
    return 'html';
  }

  /**
   * @return string
   */
  public function getHost() {
    return $this->host;
  }

  /**
   * @return string
   */
  public function getUser() {
    return $this->user;
  }

  public function getShortHostName() {
    // User is unique.
    return 'uberspace';
  }

}
