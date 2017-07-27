<?php
/**
 * @file Installation.php
 */

namespace machbarmacher\localsettings;

use machbarmacher\localsettings\RenderPhp\PhpFile;

class Installation extends InstallationBase implements InstallationInterface {
  public function compileAliases(PhpFile $php) {
    $php->addRawStatement('');
    $php->addRawStatement("// Installation: $this->name");
    // Name in curly braces? Then glob docroot.
    $multisite = count($this->site_uris) !== 1;
    $site_list= [];

    $installation_name_expression = $this->name;
    // Add single site aliases.
    foreach ($this->site_uris as $site => $uris) {
      $alias_name = $multisite ? $this->name . '.' . $site : $this->name;
      $uri = $uris[0];
      $alias = [
        // Only use primary uri.
        'remote-user' => $this->server->getUser(),
        'remote-host' => $this->server->getHost(),
        'root' => $this->docroot,
        'uri' => $uri,
        '#unique_site_name' => $this->getUniqueSiteName($site),
      ];
      if ($this->drush_environment_variables) {
        $alias['#env-vars'] = $this->drush_environment_variables;
      }
      $this->server->alterAlias($alias);
      $alias_exported = var_export($alias, TRUE);
      $php->addRawStatement("\$aliases[\"$alias_name\"] = $alias_exported;");
      $site_list[] = "@$alias_name";
    }
    if ($multisite) {
      // Add site-list installation alias.
      $site_list_exported = var_export(['site-list' => $site_list], TRUE);
      $php->addRawStatement("\$aliases['$installation_name_expression'] = $site_list_exported;");
    }
  }

  public function isCurrent() {
    return $this->isLocal() && realpath($this->docroot) == realpath(DRUSH_DRUPAL_CORE);
  }

}
