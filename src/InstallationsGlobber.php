<?php

namespace machbarmacher\localsettings;

use machbarmacher\localsettings\RenderPhp\ArrayX;
use machbarmacher\localsettings\RenderPhp\StringDoubleQuoted;
use machbarmacher\localsettings\RenderPhp\PhpFile;
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
    return new StringDoubleQuoted("$this->declaration_name-\$installation_suffix") ;
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
    $placeholder = preg_quote('{{installation-suffix}}', $delimiter);
    $glob_pattern = (new Replacements())->register($placeholder, '(.*)')
      ->apply($docroot);
    return "{$delimiter}$glob_pattern{$delimiter}u";
  }

  public function compileEnvironmentInfo(PhpFile $php, Replacements $replacements) {
    parent::compileEnvironmentInfo($php, $replacements);

    $replacements->register('{{installation-suffix}}', '{$installation_suffix}');

    $settings_variable = $this->project->getSettingsVariable();
    $installationSuffixX = $this->makeInstallationSuffixExpressionForSettings();
    $php->addRawStatement(<<<EOD
\$installation_suffix = {$settings_variable}['localsettings']['installation_suffix'] = $installationSuffixX;
EOD
    );
  }

  /**
   * @param \machbarmacher\localsettings\RenderPhp\PhpFile $php
   */
  public function compileAliases(PhpFile $php) {
    $replacements = new Replacements();

    $php->addRawStatement('');
    $php->addRawStatement("// Declaration: $this->declaration_name");

    $is_local = $this->server->getRuntimeIsLocalCheck();
    // If nonlocal, add default installations.
    $defaultInstallationsX = ArrayX::fromLiteral($this->default_installations);
    $defaultInstallationsX->setMultiline(FALSE);
    $docroot_glob_pattern = $this->docrootPatternForGlob();
    $php->addRawStatement("\$docroots = ($is_local) ?");
    $php->addRawStatement("  glob('$docroot_glob_pattern') :");
    $php->addRawStatement("  array_map(function(\$v) {return preg_replace('/\*/', \$v, '$docroot_glob_pattern');}, $defaultInstallationsX);");
    $php->addRawStatement('foreach ($docroots as $docroot) {');
    // First quote the docroot for later, then replace the quoted wildcard.
    $docroot_preg_pattern = $this->docrootPatternForPreg();
    // Code to get the name from the docroot.
    $php->addRawStatement("  \$installation_suffix = preg_replace('$docroot_preg_pattern', '\\1', \$docroot);");
    $php->addRawStatement("  \$installation = \"$this->declaration_name-\$installation_suffix\";");
    $replacements->register('{{installation-suffix}}', '{$installation_suffix}');
    $replacements->register('{{installation}}', '{$installation}');

    $aliasBaseX = new StringDoubleQuoted($replacements->apply("{{installation}}"));
    $docrootX = new StringDoubleQuoted('$docroot');

    $this->compileAlias($php, $replacements, $aliasBaseX, $docrootX);

    $php->addRawStatement("}");
  }

  public function isCurrent() {
    if (defined('DRUPAL_ROOT') && $this->isLocal()) {
      $drupal_root_realpath = realpath(DRUPAL_ROOT);
      foreach (glob($this->docrootPatternForGlob()) as $path) {
        if (realpath($path) == $drupal_root_realpath) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

}
