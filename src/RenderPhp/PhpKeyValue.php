<?php

namespace machbarmacher\localsettings\RenderPhp;

use machbarmacher\localsettings\RenderPhp\PhpExpressionInterface;

class PhpKeyValue implements PhpRenderableInterface {
  /** @var PhpExpressionInterface|null */
  protected $key;
  /** @var PhpExpressionInterface */
  protected $value;

  /**
   * PhpKeyValue constructor.
   * @param PhpExpressionInterface $value
   * @param PhpExpressionInterface $key
   */
  public function __construct(PhpExpressionInterface $value, PhpExpressionInterface $key = NULL) {
    $this->value = $value;
    $this->key = $key;
  }

  public function __toString() {
    return isset($this->key) ? "$this->key => $this->value" : "$this->value";
  }

}
