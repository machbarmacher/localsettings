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
  /** @var IDeclaration[] */
  protected $declarations = [];

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
   * @param IServer $server
   * @return IDeclaration
   */
  public function addInstallation($name, IServer $server) {
    if (isset($this->declarations[$name])) {
      throw new \UnexpectedValueException(sprintf('Duplicate definition: %s', $name));
    }
    $declaration = new Installation($name, $server, $this);
    $this->declarations[$name] = $declaration;
    return $declaration;
  }

  /**
   * @param string $name
   * @param IServer $server
   * @return IDeclaration
   */
  public function globInstallations($name, IServer $server) {
    if (isset($this->declarations[$name])) {
      throw new \UnexpectedValueException(sprintf('Duplicate definition: %s', $name));
    }
    $declaration = new InstallationsGlobber($name, $server, $this);
    $this->declarations[$name] = $declaration;
    return $declaration;
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
   * @return IDeclaration[]
   */
  public function getDeclarations() {
    return $this->declarations;
  }

}
