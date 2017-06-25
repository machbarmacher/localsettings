<?php

namespace machbarmacher\localsettings;

use machbarmacher\localsettings\Tools\DbCredentialTools;

class InstallationValues {
  /** @var string */
  protected $name;
  /** @var ServerInterface */
  protected $server;
  /** @var Project */
  protected $project;
  /** @var string[][] */
  protected $site_uris;
  /** @var string */
  protected $docroot;
  /** @var array[] */
  protected $db_credentials = [];
  /** @var string[] */
  protected $drush_environment_variables;

  /**
   * Installation constructor.
   *
   * @param string $name
   * @param ServerInterface $server
   * @param Project $project
   */
  public function __construct($name, ServerInterface $server, Project $project) {
    $this->name = $name;
    $this->server = $server;
    $this->project = $project;

    $this->docroot = $this->server->makeDocrootAbsolute($this->server->getDefaultDocroot());
  }

  /**
   * @return string
   */
  public function getName() {
    return $this->name;
  }

  /**
   * @return mixed
   * @fixme Move glob to InstallationBundle subclass.
   */
  public function getCanonicalName() {
    return preg_replace('/[{}]/u', '', $this->name);
  }

  /**
   * @return \machbarmacher\localsettings\Project
   */
  public function getProject() {
    return $this->project;
  }

  /**
   * @return \machbarmacher\localsettings\ServerInterface
   */
  public function getServer() {
    return $this->server;
  }

  /**
   * @return string
   */
  public function getDocroot() {
    return $this->docroot;
  }

  /**
   * @return \string[][]
   */
  public function getSiteUris() {
    return $this->site_uris;
  }

  public function getUniqueSiteName($site) {
    $name = $this->server->getUniqueInstallationName($this);
    if ($this->isMultisite()) {
      $name .= "#$site";
    }
    return $name;
  }

  public function isMultisite() {
    return (bool)array_diff_key($this->site_uris, ['default' => TRUE]);
  }

  /**
   * @return $this
   */
  public function validate() {
    if (!$this->site_uris) {
      throw new \UnexpectedValueException(sprintf('Installation %s needs site uris.', $this->getName()));
    }
    if (!$this->docroot) {
      throw new \UnexpectedValueException(sprintf('Installation %s needs docroot.', $this->getName()));
    }
    return $this;
  }

  /**
   * @param string $uri
   * @param string $site
   * @return $this
   */
  public function addSite($uri, $site = 'default') {
    // @todo Validate uri.
    if (isset($this->site_uris[$site])) {
      throw new \UnexpectedValueException(sprintf('Site %s double-defined in installation %s.', $site, $this->getName()));
    }
    $this->site_uris[$site] = [$uri];
    return $this;
  }

  /**
   * @param string $uri
   * @param string $site
   * @return $this
   */
  public function addUri($uri, $site = 'default') {
    // @todo Validate uri.
    if (empty($this->site_uris[$site])) {
      throw new \UnexpectedValueException(sprintf('Additional uri %s defined for missing site %s in installation %s.', $uri, $site, $this->getName()));
    }
    if (in_array($uri, $this->site_uris[$site])) {
      throw new \UnexpectedValueException(sprintf('Additional uri %s duplicates already defined one in installation %s.', $uri, $this->getName()));
    }
    $this->site_uris[$site][] = $uri;
    return $this;
  }

  /**
   * @param string $docroot
   * @return $this
   */
  public function setDocroot($docroot) {
    $this->docroot = $this->server->makeDocrootAbsolute($docroot);
    return $this;
  }

  /**
   * @param array|string $credentials
   * @param string $site
   * @return $this
   */
  public function setDbCredentials($credentials, $site = 'default') {
    if (is_string($credentials)) {
      $credentials = DbCredentialTools::getDbCredentialsFromDbUrl($credentials);
    }
    $this->db_credentials[$site] = $credentials;
    return $this;
  }

  /**
   * @param array|string $credential_pattern
   * @return $this
   */
  public function setDbCredentialPattern($credential_pattern) {
    if (is_string($credential_pattern)) {
      $credential_pattern = DbCredentialTools::getDbCredentialsFromDbUrl($credential_pattern);
    }
    foreach ($this->site_uris as $site => $_) {
      $replacements = ['{{installation}}' => $this->name, '{{site}}' => $site, '{{dirname}}' => '$dirname'];
      $this->db_credentials[$site] = DbCredentialTools::substituteInDbCredentials($credential_pattern, $replacements);
    }
    return $this;
  }

  /**
   * @param string $name
   * @param string $value
   */
  public function setDrushEnvironmentVariable($name, $value) {
    $this->drush_environment_variables[$name] = $value;
  }

}
