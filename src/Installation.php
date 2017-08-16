<?php
/**
 * @file Installation.php
 */

namespace machbarmacher\localsettings;

use machbarmacher\localsettings\RenderPhp\PhpFile;
use machbarmacher\localsettings\RenderPhp\StringSingleQuoted;
use machbarmacher\localsettings\Tools\Replacements;

class Installation extends AbstractDeclaration implements IDeclaration {
  public function compileAliases(PhpFile $php) {
    $aliasBaseX = new StringSingleQuoted($this->declaration_name);
    $docrootX = new StringSingleQuoted($this->docroot);
    $this->compileAlias($php, new Replacements(), $aliasBaseX, $docrootX);
  }

  public function isCurrent() {
    return $this->isLocal() && realpath($this->docroot) == realpath(DRUSH_DRUPAL_CORE);
  }

  protected function makeInstallationExpressionForSettings() {
    return new StringSingleQuoted($this->declaration_name);
  }

  protected function makeInstallationSuffixExpressionForSettings() {
    $suffix = substr($this->declaration_name, strlen($this->environment_name) + 1);
    return new StringSingleQuoted($suffix);
  }

}
