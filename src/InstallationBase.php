<?php
/**
 * @file InstallationBase.php
 */

namespace clever_systems\mmm2;


abstract class InstallationBase implements InstallationInterface {
  /** @var string */
  protected $name;
  /** @var ServerInterface */
  protected $server;
  /** @var string */
  protected $uri;

  /**
   * Installation constructor.
   * @param string $name
   * @param \clever_systems\mmm2\ServerInterface $server
   * @param string $uri
   */
  public function __construct($name, $server, $uri, array $options = []) {
    $this->name = $name;
    $this->server = $server;
    $this->uri = $uri;
    $this->checkOptions($options);
    $this->setOptions($options);
  }

  abstract protected function setOptions(array $options);

  /**
   * @param array $options
   */
  protected function checkOptions(array $options) {
    $default_options = $this->getDefaultOptions();
    if ($unknown_options = array_keys(array_diff_key($options, $default_options))) {
      throw new \UnexpectedValueException(sprintf('Unknown options: %s', implode(', ', $unknown_options)));
    }
  }

  abstract protected function getDefaultOptions();

}
