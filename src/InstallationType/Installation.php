<?php
/**
 * @file Installation.php
 */

namespace clever_systems\mmm_builder\InstallationType;


use clever_systems\mmm_builder\InstallationBase;
use clever_systems\mmm_builder\InstallationInterface;
use clever_systems\mmm_builder\ServerInterface;
use clever_systems\mmm_builder\Tools\DbCredentialTools;

class Installation extends InstallationBase implements InstallationInterface {
  /** @var string */
  protected $name;
  /** @var ServerInterface */
  protected $server;
  /** @var string[] */
  protected $site_uris;
  /** @var string */
  protected $docroot;
  /** @var array[] */
  protected $db_credentials = [];

  /**
   * Installation constructor.
   * @param string $name
   * @param \clever_systems\mmm_builder\ServerInterface $server
   * @param string[string]|string $site_uris
   */
  public function __construct($name, $server, $site_uris) {
    $this->name = $name;
    $this->server = $server;
    $this->setSiteUris($site_uris);
    $this->docroot = $this->server->getDefaultDocroot();
  }

  /**
   * @param $site_uris
   */
  protected function setSiteUris($site_uris) {
    if (is_string($site_uris)) {
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
  }

  public function setDocroot($docroot) {
    $this->docroot = $this->server->normalizeDocroot($docroot);
  }

  public function setDbCredentials($credentials, $site = 'default') {
    if (is_string($credentials)) {
      $credentials = DbCredentialTools::getDbCredentialsFromDbUrl($credentials);
    }
    $this->db_credentials[$site] = $credentials;
  }

  public function setDbCredentialPattern($credential_pattern) {
    if (is_string($credential_pattern)) {
      $credential_pattern = DbCredentialTools::getDbCredentialsFromDbUrl($credential_pattern);
    }
    foreach ($this->site_uris as $site => $_) {
      $this->db_credentials[$site] = DbCredentialTools::substituteInDbCredentials($credential_pattern, ['{{site}}' => $site]);
    }
  }

  /**
   * @return \array[]
   */
  public function getDbCredentials() {
    return $this->db_credentials;
  }

  /**
   * @return \array[]
   */
  public function getAliases() {
    $multisite = count($this->site_uris) !== 1;
    $aliases = [];
    $site_list= [];
    // Add single site aliases.
    foreach ($this->site_uris as $site => $site_uri) {
      $alias_name = $multisite ? $this->name . '.' . $site : $this->name;
      $aliases[$alias_name] = [
        'uri' => $site_uri,
        'root' => $this->docroot,
        'remote-host' => $this->server->getHost(),
        'remote-user' => $this->server->getUser(),
      ];
      $site_list[] = "@$alias_name";

    }
    if ($multisite) {
      // Add site-list installation alias.
      $aliases[$this->name] = [
        'site-list' => $site_list,
      ];
    }
    return $aliases;
  }

  /**
   * @return \array[]
   */
  public function getUriToSiteMap() {
    // @todo Care for port when needed.
    $sites_by_uri = [];
    foreach ($this->site_uris as $site => $uri) {
      $sites_by_uri[parse_url($uri, PHP_URL_HOST)] = $site;
    }
    return $sites_by_uri;
  }

  /**
   * @return \array[]
   */
  public function getSiteId($site = 'default') {
    $user = $this->server->getUser();
    $host = $this->server->getHost();
    $path = $this->docroot;
    // $path is absolute and already has a leading slash.
    return "$user@$host$path#$site";
  }

  /**
   * @return \array[]
   */
  public function getBaseUrls() {
    $base_urls = [];
    foreach ($this->site_uris as $site => $site_uri) {
      $base_urls[$this->getSiteId($site)] = $site_uri;
    }
    return $base_urls;
  }

}
