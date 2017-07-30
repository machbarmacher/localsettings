<?php

namespace machbarmacher\localsettings\RenderPhp;

abstract class AbstractSingleValueExpression implements IPhpExpression {
  protected $value;

  /**
   * Constructor.
   *
   * @param $value
   */
  public function __construct($value) {
    $this->value = $value;
  }

  /**
   * @return mixed
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * @param mixed $value
   */
  public function setValue($value) {
    $this->value = $value;
  }

}
