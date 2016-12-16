<?php
/**
 * @file Installation.php
 */

namespace clever_systems\mmm2\InstallationType;


use clever_systems\mmm2\InstallationBase;
use clever_systems\mmm2\InstallationInterface;

class Installation extends InstallationBase implements InstallationInterface {
  /** @var string */
  protected $docroot;

  /**
   * @return array
   */
  protected function getDefaultOptions() {
    $default_options = [
      'docroot' => $this->server->getDefaultDocroot(),
    ];
    return $default_options;
  }

  protected function setOptions(array $options) {
    $this->docroot = $this->server->normalizeDocroot($options['docroot']);
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
      $sites_by_uri[parse_url($this->uri, PHP_URL_HOST)] = $site;
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
