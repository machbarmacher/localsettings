<?php


namespace clever_systems\mmm_builder\RenderPhp;


class PhpValue implements PhpCodeInterface {
  /** @var mixed */
  var $value;

  /**
   * PhpValue constructor.
   *
   * @param mixed $value
   */
  public function __construct($value) {
    $this->value = $value;
  }
  
  public function __toString() {
    // Cast NULL to empty string.
    return (string)var_export($this->value, TRUE);
  }
}
