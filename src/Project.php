<?php
/**
 * @file Project.php
 */

namespace clever_systems\mmm2;


class Project {
  /** @var Installation[] */
  protected $installations;

  /**
   * @param \clever_systems\mmm2\Installation $installation
   */
  public function addInstallation(Installation $installation) {
    $this->installations[] = $installation;
  }
}