<?php

namespace machbarmacher\localsettings\RenderPhp;

class PhpLiteralValue extends AbstractSingleValueExpression implements IPhpExpression {

  public function __toString() {
    // Typehint to calm the IDE.
    /** @var string $return */
    $return = var_export($this->value, TRUE);
    return $return;
  }

}