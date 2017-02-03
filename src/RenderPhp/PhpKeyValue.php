<?php


namespace clever_systems\mmm_builder\RenderPhp;


class PhpKeyValue implements PhpCodeInterface {
  /** @var PhpCodeInterface */
  protected $key;
  /** @var PhpCodeInterface */
  protected $value;

  /**
   * PhpKeyValue constructor.
   *
   * @param PhpCodeInterface $key
   * @param PhpCodeInterface $value
   */
  public function __construct(PhpCodeInterface $key, PhpCodeInterface $value) {
    $this->key = $key;
    $this->value = $value;
  }

  /**
   * @return string
   */
  public function __toString() {
    // Cast NULL to empty string.
    return "$this->key => $this->value,";
  }

}
