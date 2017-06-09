<?php
/**
 * @file Installation.php
 */

namespace machbarmacher\localsettings;


use machbarmacher\localsettings\RenderPhp\PhpFile;
use machbarmacher\localsettings\Tools\DbCredentialTools;

class Installation {
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
  /** @var string */
  protected $use_environment_name;

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
    $this->use_environment_name = $name;
  }

  /**
   * @return string
   */
  public function getName() {
    return $this->name;
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
   * @return string
   */
  public function getProjektUniqueInstallationName() {
    $user = $this->server->getUser();
    $host = $this->server->getShortHostName();
    $path = $this->server->makeDocrootRelative($this->docroot);
    $name = "$user@$host:$path";
    return $name;
  }

  /**
   * @return string
   */
  public function geProjectUniqueSiteName($site) {
    $name = $this->getProjektUniqueInstallationName();
    if ($this->isMultisite()) {
      $name .= "#$site";
    }
    return $name;
  }

  public function getServerUniqueSiteName($site) {
    $name = $this->server->getServerUniqueInstallationName($this);
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

  // @fixme Let server alter.
  public function compileAliases(PhpFile $php) {
    $php->addToBody('');
    $php->addToBody("// Installation: $this->name");
    $multisite = count($this->site_uris) !== 1;
    $site_list= [];
    // Add single site aliases.
    foreach ($this->site_uris as $site => $uris) {
      // Only use primary uri.
      $uri = $uris[0];
      $alias_name = $multisite ? $this->name . '.' . $site : $this->name;
      $root = $this->docroot;
      $host = $this->server->getHost();
      $user = $this->server->getUser();
      $server_unique_site_name = $this->server->getServerUniqueInstallationName($this);
      $php->addToBody("\$aliases['$alias_name'] = [")
        ->addToBody("  'uri' => '$uri',")
        ->addToBody("  'root' => '$root',")
        ->addToBody("  '#server_unique_site_name' => '$server_unique_site_name',")
        ->addToBody('];');
      // Drush sitealias altering in root is broken, do it here.
      // https://github.com/drush-ops/drush/issues/2432
      $php->addToBody("if (!Runtime::getEnvironment()->match('$server_unique_site_name')) {")
        ->addToBody("  \$aliases['$alias_name']['remote-user'] = '$user';")
        ->addToBody("  \$aliases['$alias_name']['remote-host'] = '$host';")
        ->addToBody('}');
      $site_list[] = "@$alias_name";
    }
    if ($multisite) {
      // Add site-list installation alias.
      $site_list_quoted = array_map(function($s) {return "'$s'";}, $site_list);
      $site_list_imploded = implode(', ', $site_list_quoted);
      $php->addToBody("\$aliases['$this->name'] = [")
        ->addToBody("'site-list' => [$site_list_imploded],")
        ->addToBody('];');
    }
  }

  public function compileSitesPhp(PhpFile $php) {
    $php->addToBody('');
    $php->addToBody("// Installation: $this->name");
    foreach ($this->site_uris as $site => $uris) {
      foreach ($uris as $uri) {
        // @todo Care for port when needed.
        $host = parse_url($uri, PHP_URL_HOST);
        if ($site !== 'default') {
          $php->addToBody("\$sites['$host'] = '$site';");
        }
      }
    }
  }

  public function compileBaseUrls(PhpFile $php) {
    foreach ($this->site_uris as $site => $uris) {
      $php->addToBody("if (\$site === '$site') {");
      // Add drush "uri".
      $uri_map = array_combine($uris, $uris);
      $uri_map["http://$site"] = $uris[0];
      foreach ($uri_map as $uri_in => $uri) {
        if ($this->project->isD7()) {
          $php->addToBody("  \$base_url = '$uri'; return;");
        }
        else {
          // D8 does not need base url anymore.
          $host = parse_url($uri_in, PHP_URL_HOST);
          $php->addToBody("  \$settings['trusted_host_patterns'][] = '$host';");
        }
      }
      $php->addToBody('}');
    }
  }

  public function compileDbCredentials(PhpFile $php) {
    foreach ($this->db_credentials as $site => $db_credential) {
      $php
        ->addToBody("\$databases['default']['default'] = "
          // @todo Replace with better dumper.
          . var_export(array_filter($db_credential), TRUE) . ';');
    }
  }

  public function alterHtaccess($content) {
    return $this->server->alterHtaccess($content);
  }

}
