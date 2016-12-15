<?php
/**
 * @file Installation.php
 */

namespace clever_systems\mmm2\InstallationType;


use clever_systems\mmm2\InstallationBase;
use clever_systems\mmm2\InstallationInterface;
use clever_systems\mmm2\ServerInterface;

class SingleSiteInstallation extends InstallationBase implements InstallationInterface {
  /** @var string */
  protected $docroot;
  
  /**
   * @return array
   */
  protected function getDefaultOptions() {
    $default_options = ['docroot' => $this->server->getDefaultDocroot()];
    return $default_options;
  }

  protected function setOptions(array $options) {
    $this->docroot = $this->server->normalizeDocroot($options['docroot']);
  }

  public function getAliases() {
    return [
      $this->name => [
        'uri' => $this->uri,
        'root' => $this->docroot,
        'remote-host' => $this->server->getHost(),
        'remote-user' => $this->server->getUser(),
      ]
    ];
  }

}
