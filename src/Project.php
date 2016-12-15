<?php
/**
 * @file Project.php
 */

namespace clever_systems\mmm2;


use clever_systems\mmm2\InstallationType\SingleSiteInstallation;

class Project {
  /** @var InstallationInterface[] */
  protected $installations;

  /**
   * @param \clever_systems\mmm2\InstallationInterface $installation
   */
  public function addInstallation(SingleSiteInstallation $installation) {
    $this->installations[] = $installation;
  }

  public function getAliases() {
    $aliases = [];
    foreach ($this->installations as $installation) {
      $aliases += $installation->getAliases();
    }
    return $aliases;
  }
}