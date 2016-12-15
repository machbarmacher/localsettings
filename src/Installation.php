<?php
/**
 * @file Installation.php
 */

namespace clever_systems\mmm2;


class Installation {
  /** @var ServerInterface */
  protected $server;
  /** @var string */
  protected $url;

  /**
   * Installation constructor.
   * @param \clever_systems\mmm2\ServerInterface $server
   * @param string $url
   */
  public function __construct($server, $url) {
    $this->server = $server;
    $this->url = $url;
  }

}