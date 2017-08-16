<?php

namespace machbarmacher\localsettings;

use machbarmacher\localsettings\Project;
use machbarmacher\localsettings\RenderPhp\PhpArray;
use machbarmacher\localsettings\RenderPhp\PhpFile;
use machbarmacher\localsettings\IServer;
use machbarmacher\localsettings\RenderPhp\PhpRawExpression;
use machbarmacher\localsettings\Tools\Replacements;

/**
 * Class InstallationGlobber
 * @package machbarmacher\localsettings
 *
 * A InstallationGlobber discovers installations by directory globbing and
 * (if local) returns installations. It is responsible for aliases (as dynamic
 * code is involved) though.
 */
class InstallationsGlobber extends AbstractDeclaration {
  protected $default_installations;

  public function __construct($declaration_name, IServer $server, Project $project) {
    parent::__construct($declaration_name, $server, $project);
    $this->default_installations = [$declaration_name];
  }

  /**
   * @param mixed $default_installations
   */
  public function defaultInstallations($default_installations) {
    $this->default_installations = $default_installations;
  }

  protected function makeInstallationExpressionForSettings() {
    return "\"$this->declaration_name-\$installation_suffix\"";
  }

  protected function makeInstallationSuffixExpressionForSettings() {
    return "basename(dirname(getcwd()))";
  }

  /**
   * Make a docroot pattern for glob like 'foo.bar/*'.'/baz'.
   *
   * @return string
   */
  private function docrootPatternForGlob() {
    $glob_pattern = (new Replacements())->register('{{installation-suffix}}', '*')
      ->apply($this->docroot);
    return $glob_pattern;
  }

  /**
   * Make a docroot pattern for preg like '#foo\.bar/(.*)/baz#u'.
   *
   * @return string
   */
  private function docrootPatternForPreg() {
    $delimiter = '#';
    $docroot = preg_quote($this->docroot, $delimiter);
    $glob_pattern = (new Replacements())->register('{{installation-suffix}}', '(.*)')
      ->apply($docroot);
    return "{$delimiter}$glob_pattern{$delimiter}u";
  }

  /**
   * @param \machbarmacher\localsettings\RenderPhp\PhpFile $php
   */
  public function compileAliases(PhpFile $php) {
    $replacements = new Replacements();

    $php->addRawStatement('');
    $php->addRawStatement("// Installation globber: $this->declaration_name");

    $is_local = $this->getLocalServerCheck();
    // If nonlocal, add default installations.
    $default_installations = PhpArray::fromLiteral($this->default_installations);
    $default_installations->setMultiline(FALSE);
    $docroot_glob_pattern = $this->docrootPatternForGlob();
    $php->addRawStatement("\$docroots = ($is_local) ?");
    $php->addRawStatement("  glob('$docroot_glob_pattern') : $default_installations;");
    $php->addRawStatement("\$aliases += ['$this->environment_name' => ['site-list' => []]];");
    $php->addRawStatement('foreach ($docroots as $docroot) {');
    // First quote the docroot for later, then replace the quoted wildcard.
    $docroot_preg_pattern = $this->docrootPatternForPreg();
    // Code to get the name from the docroot.
    $php->addRawStatement("  \$installation_suffix = preg_replace('$docroot_preg_pattern', '\\1', \$docroot);");
    $php->addRawStatement("  \$installation = \"$this->environment_name-\$installation_suffix\"");
    $replacements->register('{{installation-suffix}}', '{$installation_suffix}');
    $replacements->register('{{installation}}', '{$installation}');

    $multisite = count($this->site_uris) !== 1;
    $site_list= [];
    // Add single site aliases.
    foreach ($this->site_uris as $site => $uris) {
      $alias_name = $multisite ? "$this->declaration_name-\$installation.$site" : "$this->declaration_name-\$installation";
      $unique_site_name = (new Replacements())->register('{{site}}',  $site)->apply($this->getUniqueSiteName());
      $uri = $replacements->apply($uris[0]);
      $alias = [
        // Only use primary uri.
        'remote-user' => $this->server->getUser(),
        'remote-host' => $this->server->getHost(),
      ];
      if ($this->drush_environment_variables) {
        $alias['#env-vars'] = $this->drush_environment_variables;
      }
      $this->server->alterAlias($alias);
      $alias_exported = (new PhpRawExpression($alias));
      $php->addRawStatement("  \$aliases[\"$alias_name\"] = $alias_exported;");
      $php->addRawStatement("  \$aliases[\"$alias_name\"]['root'] = \$docroot;");
      $php->addRawStatement("  \$aliases[\"$alias_name\"]['uri'] = \"$uri\";");
      $php->addRawStatement("  \$aliases[\"$alias_name\"]['#unique_site_name'] = \"$unique_site_name\";");
      $site_list[] = "@$alias_name";
    }
    if ($multisite) {
      // Add site-list alias.
      $site_list_exported = var_export(['site-list' => $site_list], TRUE);
      $php->addRawStatement('  $aliases[\'$installation\'] = $site_list_exported;');
    }
    $php->addRawStatement("  \$aliases['$this->environment_name']['site-list'][] = \"@$this->declaration_name-\$installation\";");
    $php->addRawStatement("}");
  }

  public function isCurrent() {
    if (!$this->isLocal()) {
      return FALSE;
    }
    $drupal_root_realpath = realpath(DRUSH_DRUPAL_CORE);
    foreach (glob($this->docrootPatternForGlob()) as $path) {
      if (realpath($path) == $drupal_root_realpath) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
