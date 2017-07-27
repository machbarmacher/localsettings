<?php

namespace machbarmacher\localsettings;

use machbarmacher\localsettings\RenderPhp\PhpFile;

/**
 * Class InstallationCluster
 * @package machbarmacher\localsettings
 *
 * An installation cluster discovers installations by directory globbing and
 * (if local) returns installations. It is responsible for aliases (as dynamic
 * code is involved) though.
 */
class InstallationCluster extends InstallationBase {
  /** @var string */
  protected $raw_name;

  public function __construct($name, ServerInterface $server, Project $project) {
    // $raw_name MUST contain {foo}, $name must not.
    $raw_name = preg_match('/([{](.*)[}])/u', $name) ? $name : "{$name}";
    $name = preg_replace('/[{}]/u', '', $raw_name);
    parent::__construct($name, $server, $project);
  }

  /**
   * @param \machbarmacher\localsettings\RenderPhp\PhpFile $php
   */
  public function compileAliases(PhpFile $php) {
    $php->addRawStatement('');
    $php->addRawStatement("// Installation cluster: $this->raw_name");

    // @todo Consider $installation instead of *.
    $is_local = $this->getLocalServerCheck();
    // If nonlocal, add the canonical alias  docroot with '*' replaced by name.
    $canonical_docroot = preg_replace('/([*])/u', $this->raw_name, $this->docroot);
    $php->addRawStatement("\$docroots = !($is_local) ?");
    $php->addRawStatement("  ['$canonical_docroot'] : glob('$this->docroot') :");
    $php->addRawStatement('foreach ($docroots as $docroot) {');
    // First quote the docroot for later, then replace the quoted wildcard.
    $docroot_pattern = '#' . preg_replace('/(\\\\\*)/u', '(.*)', preg_quote($this->docroot, '#')) . '#';
    // Code to get the name from the docroot.
    $php->addRawStatement("  \$installation = preg_replace('$docroot_pattern', '\\1', \$docroot);");
    $installation_name_variable = '$installation';

    $multisite = count($this->site_uris) !== 1;
    $site_list= [];
    // Add single site aliases.
    foreach ($this->site_uris as $site => $uris) {
      $unique_site_name = $this->getUniqueSiteName($site);
      $alias_name = $multisite ? $installation_name_variable . '.' . $site : $installation_name_variable;
      $uri = $uris[0];
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
      $php->addRawStatement("  \$aliases[\"$alias_name\"]['#unique_site_name'] = \"$unique_site_name\");");
      $site_list[] = "@$alias_name";
    }
    if ($multisite) {
      // Add site-list installation alias.
      $site_list_exported = var_export(['site-list' => $site_list], TRUE);
      $php->addRawStatement("  \$aliases['$installation_name_variable'] = $site_list_exported;");
    }
    $php->addRawStatement("} // of foreach()");
  }

  public function isCurrent() {
    if (!$this->isLocal()) {
      return FALSE;
    }
    $drupal_root_realpath = realpath(DRUSH_DRUPAL_CORE);
    foreach (glob($this->docroot) as $path) {
      if (realpath($path) == $drupal_root_realpath) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
