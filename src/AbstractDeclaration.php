<?php

namespace machbarmacher\localsettings;

use machbarmacher\localsettings\RenderPhp\PhpFile;
use machbarmacher\localsettings\Tools\DbCredentialTools;

abstract class AbstractDeclaration implements IDeclaration {
  /** @var string */
  protected $declaration_name;
  /** @var string */
  protected $environment_name;
  /** @var IServer */
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

  public function __construct($declaration_name, IServer $server, Project $project) {
    $this->declaration_name = $declaration_name;
    list($this->environment_name) = explode('-', $declaration_name);
    $this->server = $server;
    $this->project = $project;

    $this->docroot = $this->server->makeDocrootAbsolute($this->server->getDefaultDocroot());
  }

  public function getDeclarationName() {
    return $this->declaration_name;
  }

  public function getEnvironmentName() {
    return $this->environment_name;
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
    if ($this->hasNonDefaultSite()) {
      $name .= "#$site";
    }
    return $name;
  }

  public function hasNonDefaultSite() {
    return (bool)array_diff_key($this->site_uris, ['default' => TRUE]);
  }

  public function validate() {
    if (!$this->site_uris) {
      throw new \UnexpectedValueException(sprintf('Declaration %s needs site uris.', $this->getDeclarationName()));
    }
    if (!$this->docroot) {
      throw new \UnexpectedValueException(sprintf('Declaration %s needs docroot.', $this->getDeclarationName()));
    }
    return $this;
  }

  public function addSite($uri, $site = 'default') {
    // @todo Validate uri.
    if (isset($this->site_uris[$site])) {
      throw new \UnexpectedValueException(sprintf('Site %s double-defined in declaration %s.', $site, $this->getDeclarationName()));
    }
    $this->site_uris[$site] = [$uri];
    return $this;
  }

  public function addUri($uri, $site = 'default') {
    // @todo Validate uri.
    if (empty($this->site_uris[$site])) {
      throw new \UnexpectedValueException(sprintf('Additional uri %s defined for missing site %s in declaration %s.', $uri, $site, $this->getDeclarationName()));
    }
    if (in_array($uri, $this->site_uris[$site])) {
      throw new \UnexpectedValueException(sprintf('Additional uri %s duplicates already defined one in declaration %s.', $uri, $this->getDeclarationName()));
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
      $replacements = ['{{site}}' => $site, '{{installation}}' => '$installation'];
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
    return eval('return ' . $this->getLocalServerCheck() . ';');
  }

  public function alterHtaccess($content) {
    return $this->server->alterHtaccess($content);
  }

  public function compileSitesPhp(PhpFile $php) {
    $php->addRawStatement('');
    $php->addRawStatement("// Installation: $this->declaration_name");
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
      if ($this->hasNonDefaultSite()) {
        $php->addRawStatement("if (\$site === '$site') {");
      }
      foreach ($uris as $uri) {
        // @todo Make this elegant.
        $uri = preg_replace('/{{installation}}/u', '$installation', $uri);
        if ($this->project->isD7()) {
          if (count($uris) == 1) {
            $php->addRawStatement("  \$base_url = \"$uri\";");
          }
          else {
            $php->addRawStatement("  // Drush dirname? Use the first URI.");
            $php->addRawStatement("  if(drupal_is_cli() && strpos(\$_SERVER['HTTP_HOST'], '.') === FALSE) {");
            $php->addRawStatement("    \$base_url = \"$uri\";");
            $php->addRawStatement("  }");
            $php->addRawStatement("  else {");
            $php->addRawStatement("    // Recognize any URI as this site has multiple. Assume no subdir install.");
            $php->addRawStatement("    \$base_url = (isset(\$_SERVER['HTTPS'])?'https://':'http://') . rtrim(\$_SERVER['HTTP_HOST'], '.')");
            $php->addRawStatement("  }");
          }
          // We only need to do this for the main uri.
          break;
        }
        else {
          // D8 does not need base url anymore.
          $host = parse_url($uri, PHP_URL_HOST);
          $php->addRawStatement("\$settings['trusted_host_patterns'][] = \"$host\";");
        }
      }
      if ($this->hasNonDefaultSite()) {
        $php->addRawStatement('}');
      }
    }
  }

  public function compileDbCredentials(PhpFile $php) {
    foreach ($this->db_credentials as $site => $db_credential) {
      foreach ($db_credential as $key => $value) {
        if ($value) {
          // @todo Make this elegant.
          $value = preg_replace('/{{installation}}/u', '$installation', $value);
          $php->addRawStatement("\$databases['default']['default']['$key'] = \"$value\";");
        }
      }
    }
  }

  public function compileEnvironmentInfo(PhpFile $php) {
    $settings_variable = $this->project->getSettingsVariable();

    $environment_name = $this->getEnvironmentName();
    $unique_site_name  = $this->getUniqueSiteName('$site');

    $installation_expression = $this->makeInstallationExpressionForSettings();
    $php->addRawStatement(<<<EOD
\$environment = {$settings_variable}['localsettings']['environment'] = '$environment_name';
\$installation = {$settings_variable}['localsettings']['installation'] = $installation_expression;
\$unique_site_name = {$settings_variable}['localsettings']['unique_site_name'] = "$unique_site_name";
EOD
    );
    if ($this->project->isD7()) {
      $php->addRawStatement("\$conf['master_current_scope'] = '$this->$environment_name';");
    }
  }

  /**
   * @return string
   */
  abstract protected function makeInstallationExpressionForSettings();

}
