<?php

namespace machbarmacher\localsettings\RenderPhp;

class StringSingleQuoted extends AbstractStringExpression {

  public function __toString() {
    $replacements = ["'" => "\'", '\\' => '\\\\'];
    $string = $this->replacedString($replacements);
    return "'" . $string . "'";
  }

  /**
   * @return \machbarmacher\localsettings\RenderPhp\StringDoubleQuoted
   */
  public function makeDoubleQuoted() {
    $string = $this->replacedString(['$' => '\$', '{' => '\{']);
    return new StringDoubleQuoted($string);
  }

  public function isDoubleQuoted() {
    return FALSE;
  }

}
