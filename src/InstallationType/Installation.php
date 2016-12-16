<?php
/**
 * @file Installation.php
 */

namespace clever_systems\mmm2\InstallationType;


use clever_systems\mmm2\InstallationBase;
use clever_systems\mmm2\InstallationInterface;
use clever_systems\mmm2\ServerInterface;
use clever_systems\mmm2\Tools\DbCredentialTools;

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
   * @param \clever_systems\mmm2\ServerInterface $server
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

  public function getAliases() {
    $aliases = [];
    $site_list= [];
    // Add single site aliases.
    foreach ($this->site_uris as $site => $site_uri) {
      $alias_name = $this->name . '.' . $site;
      $aliases[$alias_name] = [
        'uri' => $site_uri,
        'root' => $this->docroot,
        'remote-host' => $this->server->getHost(),
        'remote-user' => $this->server->getUser(),
      ];
      $site_list[] = "@$alias_name";

    }
    // Add installation alias.
    $aliases[$this->name] = [
      'site-list' => $site_list,
    ];
  }

  public function getUriToSiteMap() {
    // @todo Care for port when needed.
    $sites_by_uri = [];
    foreach ($this->site_uris as $site => $uri) {
      $sites_by_uri[parse_url($uri, PHP_URL_HOST)] = $site;
    }
    return $sites_by_uri;
  }

  public function getSiteId($site = 'default') {
    $user = $this->server->getUser();
    $host = $this->server->getHost();
    $path = $this->docroot;
    return "$user@$host/$path#$site";
  }

  public function getBaseUrls() {
    $base_urls = [];
    foreach ($this->site_uris as $site => $site_uri) {
      $base_urls[$this->getSiteId($site)] = $site_uri;
    }
  }

}
