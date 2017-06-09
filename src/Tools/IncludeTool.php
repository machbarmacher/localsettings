<?php

namespace machbarmacher\localsettings\Tools;

class IncludeTool {
  public static function getVariables($file) {
    include $file;
    return get_defined_vars();
  }
}
