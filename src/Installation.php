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

  public function compileAliases(PhpFile $php) {
    // Name in curly braces? Then glob docroot.
    $glob_docroot = preg_match('/\{.*\}/', $this->name);
    $host = $this->server->getHost();
    $user = $this->server->getUser();
    if ($glob_docroot) {
      $is_local = $this->server->getLocalServerCheck("'$host'", "'$user'");
      $canonical_name = preg_replace('/[{}]/u', '', $this->name);
      $docroot_wildcards = '/(\*|\{.*\})/';
      $canonical_docroot = preg_replace($docroot_wildcards, $canonical_name, $this->docroot);
      $docroot_pattern = '#' . preg_replace('/(\\\\\*|\{.*\})/u', '(.*)', preg_quote($this->docroot, '#')) . '#';
      preg_match($docroot_wildcards, $this->docroot, $matches);
      $docroot_replacements = implode('', array_map(function($v) {
        return "\\$v";
      }, range(1, count($matches) - 1)));
      $php->addRawStatement("if ($is_local) {");
      $php->addRawStatement("  \$docroots = glob('$this->docroot')");
      $php->addRawStatement("  \$docroots = array_combine(preg_replace('$docroot_pattern', '$docroot_replacements', \$docroot), \$docroot);");
      $php->addRawStatement("}");
      $php->addRawStatement("else {");
      $php->addRawStatement("  \$docroots = [$canonical_name => '$canonical_docroot'];");
      $php->addRawStatement("}");
      $php->addRawStatement("foreach (\$docroots as \$docroot) {");
    }
    $php->addRawStatement('');
    $php->addRawStatement("// Installation: $this->name");
    $multisite = count($this->site_uris) !== 1;
    $site_list= [];
    // Add single site aliases.
    foreach ($this->site_uris as $site => $uris) {
      $alias_name = $multisite ? $this->name . '.' . $site : $this->name;
      $alias = [
        // Only use primary uri.
        'uri' => $uris[0],
        'remote-user' => $user,
        'remote-host' => $host,
        '#unique_site_name' => $this->getUniqueSiteName($site),
      ];
      if (!$glob_docroot) {
        $alias['root'] = $this->docroot;
      }
      if ($this->drush_environment_variables) {
        $alias['#env-vars'] = $this->drush_environment_variables;
      }
      $this->server->alterAlias($alias);
      $alias_exported = var_export($alias, TRUE);
      $php->addRawStatement("\$aliases['$alias_name'] = $alias_exported;");
      if ($glob_docroot) {
        $php->addRawStatement("\$aliases['$alias_name']['root] = \$docroot;");
      }
      $site_list[] = "@$alias_name";
    }
    if ($multisite) {
      // Add site-list installation alias.
      $site_list_exported = var_export(['site-list' => $site_list], TRUE);
      $php->addRawStatement("\$aliases['$this->name'] = $site_list_exported;");
    }
    if ($glob_docroot) {
      $php->addRawStatement("}");
    }
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
        // We assume $ always denotes a variable.
        $value_quoted = (strpos($value, '$') !== FALSE) ? '"$value"' : "'$value'";
        $php->addRawStatement("\$databases['default']['default']['$key'] = $value_quoted;");
      }
    }
  }

  public function alterHtaccess($content) {
    return $this->server->alterHtaccess($content);
  }

}
