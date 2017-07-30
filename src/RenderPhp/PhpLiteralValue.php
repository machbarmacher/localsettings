<?php

namespace machbarmacher\localsettings\RenderPhp;

class PhpLiteralValue extends AbstractSingleValueExpressionBase implements PhpExpressionInterface {

  public function __toString() {
    return var_export($this->value);
  }

}
