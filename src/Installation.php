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
    $this->compileAlias($php, new Replacements(),
      new StringSingleQuoted($this->declaration_name),
      new StringSingleQuoted($this->docroot));
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
