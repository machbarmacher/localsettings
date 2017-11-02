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
\$this_installation = \$this_server = [];
foreach (\$aliases as \$alias_name => &\$alias) {
  if (!isset(\$alias['remote-host'])) {
    \$this_server["@\$alias_name"] = \$alias;
    if (defined('DRUPAL_ROOT') && realpath(\$alias['root']) === realpath(DRUPAL_ROOT)) {
      \$this_installation["@\$alias_name"] = \$alias;
    }
  }
}
if (count(\$this_installation) == 1) {
  \$aliases['this-installation'] = reset(\$this_installation);
}
else {
  \$aliases['this-installation'] = ['site-list' => array_keys(\$this_installation)];
}
if (count(\$this_server) == 1) {
  \$aliases['this-server'] = reset(\$this_server);
}
else {
  \$aliases['this-server'] = ['site-list' => array_keys(\$this_server)];
}

EOD
    );
  }

}
