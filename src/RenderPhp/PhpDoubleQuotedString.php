<?php

namespace machbarmacher\localsettings\RenderPhp;

class PhpDoubleQuotedString extends AbstractStringExpression {

  public function __toString() {
    $replacements = ['"' => '\"', '\\' => '\\\\'];
    $string = $this->replacedString($replacements);
    return '"' . $string . '"';
  }

  public function isDoubleQuoted() {
    return TRUE;
  }

}
