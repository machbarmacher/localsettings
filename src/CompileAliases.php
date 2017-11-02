<?php

namespace machbarmacher\localsettings;

use machbarmacher\localsettings\RenderPhp\PhpFile;
use machbarmacher\localsettings\RenderPhp\PhpRawStatement;
use machbarmacher\localsettings\RenderPhp\Statements;

class CompileAliases {

  /**
   * @param \machbarmacher\localsettings\RenderPhp\PhpFile $php
   * @param \machbarmacher\localsettings\IDeclaration[] $declarations
   */
  public static function addAliasAlterCode(PhpFile $php, $declarations) {
    $php->addRawStatement(<<<EOD

// Alter local aliases and add @this-installation here as the drush alter hook is broken. 
\$current_sites = \$local_sites = [];
foreach (\$aliases as \$alias_name => &\$alias) {
  if (!isset(\$alias['remote-host'])) {
    \$local_sites["@\$alias_name"] = \$alias;
  }

  \$is_current = \$is_local && defined('DRUPAL_ROOT') && realpath(\$alias['root']) === realpath(DRUPAL_ROOT);
  if (\$is_current) {
    \$current_sites["@\$alias_name"] = \$alias;
  }
}
if (count(\$current_sites) == 1) {
  \$aliases['this-installation'] = reset(\$current_sites);
}
else {
  \$aliases['this-installation'] = ['site-list' => array_keys(\$current_sites)];
}
if (count(\$local_sites) == 1) {
  \$aliases['this-server'] = reset(\$local_sites);
}
else {
  \$aliases['this-server'] = ['site-list' => array_keys(\$local_sites)];
}

EOD
    );
  }

}
