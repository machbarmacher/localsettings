<?php
/**
 * @file Freistilbox.php
 */

namespace clever_systems\mmm_builder\ServerType;


use clever_systems\mmm_builder\ServerBase;
use clever_systems\mmm_builder\ServerInterface;

/**
 * Class Freistilbox
 * @package clever_systems\mmm_builder\ServerType
 *
 * @todo Implement repository branch & url & environment
 * repository url = ssh://{{site-handle}}@repo.freistilbox.net/~/site
 */
class FreistilboxServer extends ServerBase implements ServerInterface {
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

  public function getDefaultDocroot() {
    return 'current/docroot';
  }

  public function getHost() {
    return $this->cluster . 's.freistilbox.net';
  }

  public function getUser() {
    return $this->site_handle;
  }

  public function getUserHome() {
    return '/srv/www/freistilbox/home/' . $this->site_handle;
  }

  public function getShortHostName() {
    // User is unique and we can't access host on runtime.
    return 'freistilbox';
  }

  public function alterHtaccess($content) {
    return <<<EOD
# START Freistilbox
RewriteBase /
Options +FollowSymLinks
# END Freistilbox


EOD
      . $content;
  }

}
