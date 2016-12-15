<?php
/**
 * @file Freistilbox.php
 */

namespace clever_systems\mmm2\ServerType;


use clever_systems\mmm2\ServerInterface;

class Freistilbox extends ServerInterface {
  /** @var string */
  protected $cluster;
  /** @var string */
  protected $site_handle;

  /**
   * Freistilbox constructor.
   * @param string $cluster
   * @param string $site_handle
   */
  public function __construct($cluster, $site_handle) {
    $this->cluster = $cluster;
    $this->site_handle = $site_handle;
  }


}