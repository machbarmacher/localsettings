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
    $drupal_root = drush_get_context('DRUSH_DRUPAL_ROOT');
    $isLocal = $this->isLocal();
    // We compare dirnames here, as web/docroot subdir can change and we can
    // safely assume only a single docroot per installation dir.
    $docroot_parent = realpath(dirname($this->docroot));
    $current_docroot_parent = realpath(dirname($drupal_root));
    $locationMatch = $docroot_parent == $current_docroot_parent;
    return $isLocal && $locationMatch;
  }

  protected function makeInstallationExpressionForSettings() {
    return new StringSingleQuoted($this->declaration_name);
  }

  protected function makeInstallationSuffixExpressionForSettings() {
    $suffix = substr($this->declaration_name, strlen($this->environment_name) + 1);
    return new StringSingleQuoted($suffix);
  }

}
