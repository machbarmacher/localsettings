<?php

namespace machbarmacher\localsettings\RenderPhp;

use machbarmacher\localsettings\RenderPhp\IPhpExpression;

class PhpKeyValue implements IPhpRenderable {
  /** @var IPhpExpression|null */
  protected $key;
  /** @var IPhpExpression */
  protected $value;

  /**
   * PhpKeyValue constructor.
   * @param IPhpExpression $value
   * @param IPhpExpression $key
   */
  public function __construct(IPhpExpression $value, IPhpExpression $key = NULL) {
    $this->value = $value;
    $this->key = $key;
  }

  public function __toString() {
    return isset($this->key) ? "$this->key => $this->value" : "$this->value";
  }

}
