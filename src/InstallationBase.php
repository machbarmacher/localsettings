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
  /** @var string[] */
  protected $site_uris;

  /**
   * Installation constructor.
   * @param string $name
   * @param \clever_systems\mmm2\ServerInterface $server
   * @param string[string]|string $site_uris
   */
  public function __construct($name, $server, $site_uris, array $options = []) {
    $this->name = $name;
    $this->server = $server;
    if (!is_array($site_uris)) {
      $site_uris = ['default' => $site_uris];
    }
    $this->site_uris = $site_uris;
    foreach ($this->site_uris as $site_uri) {
      if (preg_match('#/$#', $site_uri)) {
        throw new \UnexpectedValueException(sprintf('Site Uri must not contain trailing slash: %s', $site_uri));
      }
      if (!parse_url($site_uri)) {
        throw new \UnexpectedValueException(sprintf('Site Uri invalid: %s', $site_uri));
      }
    }
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
