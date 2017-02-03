<?php


namespace clever_systems\mmm_builder\RenderPhp;


class PhpRawCode implements PhpCodeInterface {
  /** @var string */
  protected $value;

  /**
   * PhpValue constructor.
   *
   * @param string $value
   */
  public function __construct($value) {
    $this->value = $value;
  }

  /**
   * @return string
   */
  public function __toString() {
    return $this->value;
  }
}
