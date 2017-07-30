<?php

namespace machbarmacher\localsettings;

use machbarmacher\localsettings\Project;
use machbarmacher\localsettings\RenderPhp\PhpArray;
use machbarmacher\localsettings\RenderPhp\PhpFile;
use machbarmacher\localsettings\IServer;

/**
 * Class MultiEnvironment
 * @package machbarmacher\localsettings
 *
 * A MultiEnvironment discovers installations by directory globbing and
 * (if local) returns installations. It is responsible for aliases (as dynamic
 * code is involved) though.
 */
class MultiEnvironment extends AbstractEnvironment {
  protected $default_installations;

  public function __construct($name, IServer $server, Project $project) {
    parent::__construct($name, $server, $project);
    $this->default_installations = [$name];
  }

  /**
   * @param mixed $default_installations
   */
  public function defaultInstallations($default_installations) {
    $this->default_installations = $default_installations;
  }

  protected function docrootForInstallation($installation_name, $preg_delimiter = NULL) {
    return $this->stringForInstallation($this->docroot, $installation_name, $preg_delimiter);
  }

  protected function stringForInstallation($string, $installation_name, $preg_delimiter = NULL) {
    $placeholder = '{{installation}}';
    if ($preg_delimiter) {
      $placeholder = preg_quote($placeholder, $preg_delimiter);
      $string = preg_quote($string, $preg_delimiter);
    }
    $replaced = preg_replace('/' . preg_quote($placeholder, '/') . '/u', $installation_name, $string);
    return $replaced;
  }

  public function getUniqueSiteName($site) {
    $uniqueSiteName = parent::getUniqueSiteName($site);
    // @todo Hardcoding $installation here is not very elegant.
    $uniqueSiteName = $this->stringForInstallation($uniqueSiteName, '$installation');
    return $uniqueSiteName;
  }

  protected function makeInstallationExpressionForSettings() {
    // @todo Make more general when needed.
    return 'basename(dirname(getcwd()))';
  }

  /**
   * @param \machbarmacher\localsettings\RenderPhp\PhpFile $php
   */
  public function compileAliases(PhpFile $php) {
    $php->addRawStatement('');
    $php->addRawStatement("// Installation cluster: $this->name");

    $is_local = $this->getLocalServerCheck();
    // If nonlocal, add default installations.
    $default_installations = PhpArray::fromLiteral($this->default_installations);
    $default_installations->setMultiline(FALSE);
    $docroot_glob_pattern = $this->docrootForInstallation('*');
    $php->addRawStatement("\$docroots = ($is_local) ?");
    $php->addRawStatement("  glob('$docroot_glob_pattern') : $default_installations;");
    $php->addRawStatement('foreach ($docroots as $docroot) {');
    // First quote the docroot for later, then replace the quoted wildcard.
    $docroot_pattern = '#' . $this->docrootForInstallation('(.*)', '#') . '#';
    // Code to get the name from the docroot.
    $php->addRawStatement("  \$installation = preg_replace('$docroot_pattern', '\\1', \$docroot);");
    $installation_name_variable = '$installation';

    $multisite = count($this->site_uris) !== 1;
    $site_list= [];
    // Add single site aliases.
    foreach ($this->site_uris as $site => $uris) {
      $alias_name = $multisite ? $installation_name_variable . '.' . $site : $installation_name_variable;
      $uri = $this->stringForInstallation($uris[0], '$installation');
      $unique_site_name = $this->getUniqueSiteName($site);
      $alias = [
        // Only use primary uri.
        'remote-user' => $this->server->getUser(),
        'remote-host' => $this->server->getHost(),
      ];
      if ($this->drush_environment_variables) {
        $alias['#env-vars'] = $this->drush_environment_variables;
      }
      $this->server->alterAlias($alias);
      $alias_exported = var_export($alias, TRUE);
      $php->addRawStatement("  \$aliases[\"$alias_name\"] = $alias_exported;");
      $php->addRawStatement("  \$aliases[\"$alias_name\"]['root'] = \$docroot;");
      $php->addRawStatement("  \$aliases[\"$alias_name\"]['uri'] = \"$uri\";");
      $php->addRawStatement("  \$aliases[\"$alias_name\"]['#unique_site_name'] = \"$unique_site_name\";");
      $site_list[] = "@$alias_name";
    }
    if ($multisite) {
      // Add site-list alias.
      $site_list_exported = var_export(['site-list' => $site_list], TRUE);
      $php->addRawStatement("  \$aliases['$installation_name_variable'] = $site_list_exported;");
    }
    $php->addRawStatement("}");
  }

  public function isCurrent() {
    if (!$this->isLocal()) {
      return FALSE;
    }
    $drupal_root_realpath = realpath(DRUSH_DRUPAL_CORE);
    $glob_pattern = $this->docrootForInstallation('*');
    foreach (glob($glob_pattern) as $path) {
      if (realpath($path) == $drupal_root_realpath) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
