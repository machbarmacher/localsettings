<?php
/**
 * @file Installation.php
 */

namespace machbarmacher\localsettings;

use machbarmacher\localsettings\RenderPhp\PhpFile;

class Installation extends AbstractDeclaration implements IDeclaration {
  public function compileAliases(PhpFile $php) {
    $php->addRawStatement('');
    $php->addRawStatement("// Installation: $this->declaration_name");
    $multisite = count($this->site_uris) !== 1;
    $site_list= [];

    // Add single site aliases.
    foreach ($this->site_uris as $site => $uris) {
      $alias_name = $multisite ? $this->declaration_name . '.' . $site : $this->declaration_name;
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
      // Add site-list alias.
      $site_list_exported = var_export(['site-list' => $site_list], TRUE);
      $php->addRawStatement("\$aliases['$this->declaration_name'] = $site_list_exported;");
    }
    if ($this->environment_name !== $this->declaration_name) {
      $php->addRawStatement("\$aliases += ['$this->environment_name' => ['site-list' => []]];");
      $php->addRawStatement("\$aliases['$this->environment_name']['site-list'][] = '@$this->declaration_name';");
    }
  }

  public function isCurrent() {
    return $this->isLocal() && realpath($this->docroot) == realpath(DRUSH_DRUPAL_CORE);
  }

  protected function makeInstallationExpressionForSettings() {
    return "'$this->declaration_name'";
  }

  protected function makeInstallationSuffixExpressionForSettings() {
    $suffix = substr($this->declaration_name, strlen($this->environment_name) + 1);
    return "'$suffix'";
  }

}
