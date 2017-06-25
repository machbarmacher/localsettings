<?php
/**
 * @file Installation.php
 */

namespace machbarmacher\localsettings;

use machbarmacher\localsettings\RenderPhp\PhpFile;

class Installation extends InstallationValues {
  /**
   * @param \machbarmacher\localsettings\RenderPhp\PhpFile $php
   * @fixme Move glob to InstallationBundle subclass.
   */
  public function compileAliases(PhpFile $php) {
    $php->addRawStatement('');
    $php->addRawStatement("// Installation: $this->name");
    // Name in curly braces? Then glob docroot.
    $glob_docroot = preg_match('/\{.*\}/', $this->name);
    if ($glob_docroot) {
      $is_local = $this->getLocalServerCheck();
      $wildcard = '/([*])/u';
      $canonical_name = $this->getCanonicalName();
      $canonical_docroot = preg_replace($wildcard, $canonical_name, $this->docroot);
      // First quote the docroot for later, then replace the quoted wildcard.
      $docroot_pattern = '#' . preg_replace('/(\\\\\*)/u', '(.*)', preg_quote($this->docroot, '#')) . '#';
      $php->addRawStatement("if ($is_local) {");
      $php->addRawStatement("  \$docroots = glob('$this->docroot');");
      $php->addRawStatement(<<<EOD
  \$docroots = array_combine(array_map(function(\$v) {
    return preg_replace('$docroot_pattern', '\\1', \$v);
  }, \$docroots), \$docroots);
EOD
      );
      $php->addRawStatement("}");
      $php->addRawStatement("else {");
      $php->addRawStatement("  \$docroots = ['$canonical_name' => '$canonical_docroot'];");
      $php->addRawStatement("}");
      $php->addRawStatement("foreach (\$docroots as \$name => \$docroot) {");
    }
    $multisite = count($this->site_uris) !== 1;
    $site_list= [];
    $installation_name_expression = $glob_docroot ? '$name' : $this->name;
    // Add single site aliases.
    foreach ($this->site_uris as $site => $uris) {
      $unique_site_name = $this->getUniqueSiteName($site);
      $alias_name = $multisite ? $installation_name_expression . '.' . $site : $installation_name_expression;
      $uri = $uris[0];
      $alias = [
        // Only use primary uri.
        'remote-user' => $this->server->getUser(),
        'remote-host' => $this->server->getHost(),
      ];
      if (!$glob_docroot) {
        $alias['root'] = $this->docroot;
        $alias['uri'] = $uri;
        $alias['#unique_site_name'] = $unique_site_name;
      }
      if ($this->drush_environment_variables) {
        $alias['#env-vars'] = $this->drush_environment_variables;
      }
      $this->server->alterAlias($alias);
      $alias_exported = var_export($alias, TRUE);
      $php->addRawStatement("\$aliases[\"$alias_name\"] = $alias_exported;");
      if ($glob_docroot) {
        $php->addRawStatement("\$aliases[\"$alias_name\"]['root'] = \$docroot;");
        $php->addRawStatement("\$aliases[\"$alias_name\"]['uri'] = preg_replace('/[*]/', \$name, '$uri');");
        $php->addRawStatement("\$aliases[\"$alias_name\"]['#unique_site_name'] = preg_replace('/[*]/', \$name, '$unique_site_name');");
      }
      $site_list[] = "@$alias_name";
    }
    if ($multisite) {
      // Add site-list installation alias.
      $site_list_exported = var_export(['site-list' => $site_list], TRUE);
      $php->addRawStatement("\$aliases['$installation_name_expression'] = $site_list_exported;");
    }
    if ($glob_docroot) {
      $php->addRawStatement("} // of foreach()");
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

  /**
   * @return mixed
   */
  public function getLocalServerCheck() {
    $host = $this->server->getHost();
    $user = $this->server->getUser();
    $is_local = $this->server->getLocalServerCheck("'$host'", "'$user'");
    return $is_local;
  }

  public function isLocal() {
    $is_server = eval($this->getLocalServerCheck());
    $is_local = $is_server && $this->isCurrent();
    return $is_local;
  }

  /**
   * @return bool
   *
   * @fixme Move glob to InstallationBundle subclass.
   */
  public function isCurrent() {
    $current = realpath(DRUSH_DRUPAL_CORE);
    foreach (glob($this->docroot) as $path) {
      if (realpath($path) == $current) {
        return TRUE;
      }
    }
    return FALSE;
  }
}
