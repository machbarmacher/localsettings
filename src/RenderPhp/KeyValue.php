<?php

namespace machbarmacher\localsettings\RenderPhp;

use machbarmacher\localsettings\RenderPhp\IExpression;

class KeyValue implements IRenderable {
  /** @var IExpression|null */
  protected $key;
  /** @var IExpression */
  protected $value;

  /**
   * PhpKeyValue constructor.
   * @param IExpression $value
   * @param IExpression $key
   */
  public function __construct(IExpression $value, IExpression $key = NULL) {
    $this->value = $value;
    $this->key = $key;
  }

  public function __toString() {
    return isset($this->key) ? "$this->key => $this->value" : "$this->value";
  }

}
