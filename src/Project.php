<?php
/**
 * @file Project.php
 */

namespace clever_systems\mmm_builder;

use clever_systems\mmm_builder\InstallationType\Installation;

/**
 * Class Project
 * @package clever_systems\mmm_builder
 *
 * @todo Add docroot relative to gitroot setting.
 */
class Project {
  /** @var InstallationInterface[] */
  protected $installations;

  /**
   * @param string $name
   * @param ServerInterface $server
   * @return Installation
   */
  public function addInstallation($name, ServerInterface $server) {
    if (isset($this->installations[$name])) {
      throw new \UnexpectedValueException(sprintf('Duplicate installation: %s', $name));
    }
    $installation = new Installation($name, $server);
    $this->installations[$name] = $installation;
    return $installation;
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
