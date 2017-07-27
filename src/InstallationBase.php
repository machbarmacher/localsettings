<?php

namespace machbarmacher\localsettings;

use machbarmacher\localsettings\RenderPhp\PhpFile;
use machbarmacher\localsettings\Tools\DbCredentialTools;

abstract class InstallationBase implements InstallationInterface {
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

  public function __construct($name, ServerInterface $server, Project $project) {
    $this->name = $name;
    $this->server = $server;
    $this->project = $project;

    $this->docroot = $this->server->makeDocrootAbsolute($this->server->getDefaultDocroot());
  }

  public function getName() {
    return $this->name;
  }

  public function getProject() {
    return $this->project;
  }

  public function getServer() {
    return $this->server;
  }

  public function getDocroot() {
    return $this->docroot;
  }

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

  public function validate() {
    if (!$this->site_uris) {
      throw new \UnexpectedValueException(sprintf('Installation %s needs site uris.', $this->getName()));
    }
    if (!$this->docroot) {
      throw new \UnexpectedValueException(sprintf('Installation %s needs docroot.', $this->getName()));
    }
    return $this;
  }

  public function addSite($uri, $site = 'default') {
    // @todo Validate uri.
    if (isset($this->site_uris[$site])) {
      throw new \UnexpectedValueException(sprintf('Site %s double-defined in installation %s.', $site, $this->getName()));
    }
    $this->site_uris[$site] = [$uri];
    return $this;
  }

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

  public function setDocroot($docroot) {
    $this->docroot = $this->server->makeDocrootAbsolute($docroot);
    return $this;
  }

  public function setDbCredential($credentials, $site = 'default') {
    if (is_string($credentials)) {
      $credentials = DbCredentialTools::getDbCredentialsFromDbUrl($credentials);
    }
    $this->db_credentials[$site] = $credentials;
    return $this;
  }

  public function setDbCredentialPattern($credential_pattern) {
    if (is_string($credential_pattern)) {
      $credential_pattern = DbCredentialTools::getDbCredentialsFromDbUrl($credential_pattern);
    }
    foreach ($this->site_uris as $site => $_) {
      // @fixme Consider replacing with $foo syntax.
      $replacements = ['{{installation}}' => $this->name, '{{site}}' => $site, '{{dirname}}' => '$dirname'];
      $this->db_credentials[$site] = DbCredentialTools::substituteInDbCredentials($credential_pattern, $replacements);
    }
    return $this;
  }

  public function setDrushEnvironmentVariable($name, $value) {
    $this->drush_environment_variables[$name] = $value;
  }

  /**
   * @return string
   */
  protected function getLocalServerCheck() {
    $host = $this->server->getHost();
    $user = $this->server->getUser();
    $is_local = $this->server->getLocalServerCheck("'$host'", "'$user'");
    return $is_local;
  }

  public function isLocal() {
    return eval($this->getLocalServerCheck());
  }

  public function alterHtaccess($content) {
    return $this->server->alterHtaccess($content);
  }

  public function compileSitesPhp(PhpFile $php) {
    $php->addRawStatement('');
    $php->addRawStatement("// Installation: $this->name");
    foreach ($this->site_uris as $site => $uris) {
      foreach ($uris as $uri) {
        // @todo Care for port when needed.
        $host = parse_url($uri, PHP_URL_HOST);
        if ($site !== 'default') {
          $php->addRawStatement("\$sites['$host'] = '$site';");
        }
      }
    }
  }

  public function compileBaseUrls(PhpFile $php) {
    foreach ($this->site_uris as $site => $uris) {
      if ($this->isMultisite()) {
        $php->addRawStatement("if (\$site === '$site') {");
      }
      foreach ($uris as $uri) {
        if ($this->project->isD7()) {
          $php->addRawStatement("  // Drush?");
          $php->addRawStatement("  if(drupal_is_cli() && strpos(\$_SERVER['HTTP_HOST'], '.') === FALSE) {");
          $php->addRawStatement("    \$base_url = '$uri';");
          $php->addRawStatement("  }");
          $php->addRawStatement("  else {");
          $php->addRawStatement("    // Assume no subdir install.");
          $php->addRawStatement("    \$base_url = (isset(\$_SERVER['HTTPS'])?'https://':'http://') . rtrim(\$_SERVER['HTTP_HOST'], '.')");
          $php->addRawStatement("  }");
          // We only need to do this for the main uri.
          break;
        }
        else {
          // D8 does not need base url anymore.
          $host = parse_url($uri, PHP_URL_HOST);
          $php->addRawStatement("  \$settings['trusted_host_patterns'][] = '$host';");
        }
      }
      if ($this->isMultisite()) {
        $php->addRawStatement('}');
      }
    }
  }

  public function compileDbCredentials(PhpFile $php) {
    foreach ($this->db_credentials as $site => $db_credential) {
      foreach ($db_credential as $key => $value) {
        $php->addRawStatement("\$databases['default']['default']['$key'] = \"$value\";");
      }
    }
  }

}