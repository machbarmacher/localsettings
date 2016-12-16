<?php
/**
 * @file Project.php
 */

namespace clever_systems\mmm2;

use clever_systems\mmm2\InstallationType\Installation;

/**
 * Class Project
 * @package clever_systems\mmm2
 *
 * @todo Add docroot relative to gitroot setting.
 */
class Project {
  /** @var InstallationInterface[] */
  protected $installations;

  /**
   * @param \clever_systems\mmm2\InstallationInterface $installation
   */
  public function addInstallation(InstallationInterface $installation) {
    $this->installations[] = $installation;
  }

  public function getAliases() {
    $result = [];
    foreach ($this->installations as $installation) {
      $result += $installation->getAliases();
    }
    return $result;
  }

  public function getUriToSiteMap() {
    $result = [];
    foreach ($this->installations as $installation) {
      $result += $installation->getUriToSiteMap();
    }
    return $result;
  }

  public function getDbCredentials() {
    $result = [];
    foreach ($this->installations as $installation) {
      $result += $installation->getDbCredentials();
    }
    return $result;
  }

  public function getBaseUrls() {
    $result = [];
    foreach ($this->installations as $installation) {
      $result += $installation->getBaseUrls();
    }
    return $result;
  }

}
