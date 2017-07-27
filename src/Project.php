<?php
/**
 * @file Project.php
 */

namespace machbarmacher\localsettings;

/**
 * Class Project
 * @package machbarmacher\localsettings
 *
 * @todo Add docroot relative to gitroot setting.
 */
class Project {
  /** @var int */
  protected $drupal_major_version;
  /** @var InstallationInterface[] */
  protected $installations = [];

  /**
   * Project constructor.
   * @param int $drupal_major_version
   */
  public function __construct($drupal_major_version) {
    if (!in_array($drupal_major_version, [7, 8])) {
      throw new \UnexpectedValueException(sprintf('Drupal major version not supported: %s', $drupal_major_version));
    }
    $this->drupal_major_version = $drupal_major_version;
  }


  /**
   * @param string $name
   * @param ServerInterface $server
   * @return InstallationInterface
   */
  public function addInstallation($name, ServerInterface $server) {
    if (isset($this->installations[$name])) {
      throw new \UnexpectedValueException(sprintf('Duplicate installation: %s', $name));
    }
    $installation = new Installation($name, $server, $this);
    $this->installations[$name] = $installation;
    return $installation;
  }

  /**
   * @param string $name
   * @param ServerInterface $server
   * @return InstallationInterface
   */
  public function addInstallationsInDir($name, ServerInterface $server) {
    if (isset($this->installations[$name])) {
      throw new \UnexpectedValueException(sprintf('Duplicate installation: %s', $name));
    }
    $installation = new InstallationsInDir($name, $server, $this);
    $this->installations[$name] = $installation;
    return $installation;
  }

  /**
   * @return int
   */
  public function getDrupalMajorVersion() {
    return $this->drupal_major_version;
  }

  public function getSettingsVariable() {
    return $this->isD7() ? '$conf' : '$settings';
  }

  public function isD7() {
    return $this->drupal_major_version == 7;
  }

  /**
   * @return InstallationInterface[]
   */
  public function getInstallations() {
    return $this->installations;
  }

}
