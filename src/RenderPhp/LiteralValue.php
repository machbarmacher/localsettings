<?php

namespace machbarmacher\localsettings\RenderPhp;

class LiteralValue extends AbstractSingleValueExpression implements IExpression {

  public function __toString() {
    // Typehint to calm the IDE.
    /** @var string $return */
    $return = var_export($this->value, TRUE);
    return $return;
  }

}
