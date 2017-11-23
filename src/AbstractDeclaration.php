<?php

namespace machbarmacher\localsettings;

use machbarmacher\localsettings\RenderPhp\IStringExpression;
use machbarmacher\localsettings\RenderPhp\StringConcat;
use machbarmacher\localsettings\RenderPhp\StringDoubleQuoted;
use machbarmacher\localsettings\RenderPhp\PhpFile;
use machbarmacher\localsettings\RenderPhp\LiteralValue;
use machbarmacher\localsettings\RenderPhp\StringSingleQuoted;
use machbarmacher\localsettings\Tools\DbCredentialTools;
use machbarmacher\localsettings\Tools\Replacements;

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

  public function setEnvironmentName($environment_name) {
    $this->environment_name = $environment_name;
    return $this;
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

  public function getUniqueSiteNameWithReplacements() {
    $name = $this->server->getUniqueInstallationName($this);
    if ($this->hasNonDefaultSite()) {
      $name .= "#{{site}}";
    }
    return $name;
  }

  public function hasNonDefaultSite() {
    // @fixme Consider complementing this by hasMultipleSites
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

  public function addSites($uris) {
    // @todo Validate uris.
    if ($errors = array_intersect_key($uris, $this->site_uris)) {
      throw new \UnexpectedValueException(sprintf('Sites %s double-defined in declaration %s.', implode(', ', array_keys($errors)), $this->getDeclarationName()));
    }
    $this->site_uris += $uris;
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
    return $this;
  }

  public function isLocal() {
    return eval('return ' . $this->server->getRuntimeIsLocalCheck() . ';');
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

  public function compileBaseUrls(PhpFile $php, Replacements $replacements) {
    foreach ($this->site_uris as $site => $uris) {
      if ($this->hasNonDefaultSite()) {
        $php->addRawStatement("if (\$site === '$site') {");
      }
      foreach ($uris as $uri) {
        $uri = $replacements->apply($uri);
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

  public function compileDbCredentials(PhpFile $php, Replacements $replacements) {
    foreach ($this->db_credentials as $site => $db_credential) {
      if ($this->hasNonDefaultSite()) {
        $php->addRawStatement("if (\$site === '$site') {");
      }
      foreach ($db_credential as $key => $value) {
        if ($value) {
          $value = $replacements->apply($value);
          $php->addRawStatement("\$databases['default']['default']['$key'] = \"$value\";");
        }
      }
      if ($this->hasNonDefaultSite()) {
        $php->addRawStatement('}');
      }
    }
  }

  public function compileEnvironmentInfo(PhpFile $php, Replacements $replacements) {
    $settings_variable = $this->project->getSettingsVariable();

    $replacements->register('{{installation-suffix}}', '{$installation_suffix}');
    $replacements->register('{{installation}}', '{$installation}');
    $replacements->register('{{environment}}', '{$environment}');
    $replacements->register('{{unique-site-name}}', '{$unique_site_name}');

    $environmentNameX = new StringSingleQuoted($this->getEnvironmentName());
    $uniqueSiteNameX = new StringDoubleQuoted($replacements->apply($this->getUniqueSiteNameWithReplacements()));
    $installationSuffixX = $this->makeInstallationSuffixExpressionForSettings();
    $installationExpression = $this->makeInstallationExpressionForSettings();
    // Note: InstallationGlobber uses suffix in installation expression,
    // so order matters here.
    $php->addRawStatement(<<<EOD
\$installation_suffix = {$settings_variable}['localsettings']['installation_suffix'] = $installationSuffixX;
\$installation = {$settings_variable}['localsettings']['installation'] = $installationExpression;
\$environment = {$settings_variable}['localsettings']['environment'] = $environmentNameX;
\$unique_site_name = {$settings_variable}['localsettings']['unique_site_name'] = $uniqueSiteNameX;
EOD
    );
    if ($this->project->isD7()) {
      $php->addRawStatement("\$conf['master_current_scope'] = $environmentNameX;");
    }
  }

  abstract protected function makeInstallationExpressionForSettings();

  abstract protected function makeInstallationSuffixExpressionForSettings();

  /**
   * @param \machbarmacher\localsettings\RenderPhp\PhpFile $php
   * @param Replacements $replacements
   * @param IStringExpression $aliasBaseX
   * @param IStringExpression $docrootX
   */
  protected function compileAlias(PhpFile $php, Replacements $replacements, IStringExpression $aliasBaseX, IStringExpression $docrootX) {
    $php->addRawStatement('');
    $php->addRawStatement("// Declaration: $this->declaration_name");
    $multisite = count($this->site_uris) !== 1;
    $isLocalCheck = $this->server->getRuntimeIsLocalCheck();
    // Add single site aliases.
    foreach ($this->site_uris as $site => $uris) {
      $alias = [];
      $aliasNameX = $multisite ? new StringConcat($aliasBaseX, new StringSingleQuoted(".$site")) : $aliasBaseX;
      $replacements->register('{{site}}', $site);
      // @todo DQ only if variable.
      $uniqueSiteNameX = new StringDoubleQuoted($replacements->apply($this->getUniqueSiteNameWithReplacements()));
      $uriX = new StringDoubleQuoted($replacements->apply($uris[0]));
      if ($this->drush_environment_variables) {
        $alias['#env-vars'] = $this->drush_environment_variables;
      }
      $this->server->alterAlias($alias);
      if ($alias) {
        $aliasExpression = new LiteralValue($alias);
        $php->addRawStatement("  \$aliases[$aliasNameX] = $aliasExpression;");
      }
      $php->addRawStatement("  \$aliases[$aliasNameX]['root'] = $docrootX;");
      $php->addRawStatement("  \$aliases[$aliasNameX]['uri'] = $uriX;");
      $php->addRawStatement("  if (!($isLocalCheck)) {");
      $hostX = new StringSingleQuoted($this->server->getHost());
      $php->addRawStatement("    \$aliases[$aliasNameX]['remote-host'] = $hostX;");
      $userX = new StringSingleQuoted($this->server->getUser());
      $php->addRawStatement("    \$aliases[$aliasNameX]['remote-user'] = $userX;");
      if ($port = $this->server->getPort()) {
        $sshOptions = new StringSingleQuoted("-p $port");
        $php->addRawStatement("    \$aliases[$aliasNameX]['ssh-options'] = $sshOptions;");
      }
      $php->addRawStatement("  }");
      $php->addRawStatement("  \$aliases[$aliasNameX]['#unique_site_name'] = $uniqueSiteNameX;");
      if ($multisite) {
        $atAliasNameX = new StringConcat(new StringSingleQuoted('@'), $aliasNameX);
        $php->addRawStatement("  \$aliases[$aliasBaseX]['site-list'][] = $atAliasNameX;");
      }
    }
    // Add environment alias.
    $atAliasX = new StringConcat(new StringSingleQuoted('@'), $aliasBaseX);
    $environmentNameX = new StringSingleQuoted('environment-' . $this->environment_name);
    $php->addRawStatement("  \$aliases[{$environmentNameX}]['site-list'][] = {$atAliasX};");
  }

}
