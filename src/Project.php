<?php
/**
 * @file Project.php
 */

namespace clever_systems\mmm2;


use clever_systems\mmm2\InstallationType\Installation;

class Project {
  /** @var InstallationInterface[] */
  protected $installations;

  /**
   * @param \clever_systems\mmm2\InstallationInterface $installation
   */
  public function addInstallation(Installation $installation) {
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
  
}