<?php
/**
 * @file Project.php
 */

namespace clever_systems\mmm_builder;

/**
 * Class Project
 * @package clever_systems\mmm_builder
 *
 * @todo Add docroot relative to gitroot setting.
 */
class Project {
  /** @var Installation[] */
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

  /**
   * @return Installation[]
   */
  public function getInstallations() {
    return $this->installations;
  }

}
