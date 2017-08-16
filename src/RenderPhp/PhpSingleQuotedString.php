<?php

namespace machbarmacher\localsettings\RenderPhp;

class PhpSingleQuotedString extends AbstractStringExpression {

  public function __toString() {
    $replacements = ["'" => "\'", '\\' => '\\\\'];
    $string = $this->replacedString($replacements);
    return "'" . $string . "'";
  }

  /**
   * @return \machbarmacher\localsettings\RenderPhp\PhpDoubleQuotedString
   */
  public function makeDoubleQuoted() {
    $string = $this->replacedString(['$' => '\$', '{' => '\{']);
    return new PhpDoubleQuotedString($string);
  }

  public function isDoubleQuoted() {
    return FALSE;
  }

}
