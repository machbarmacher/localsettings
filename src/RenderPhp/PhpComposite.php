<?php


namespace clever_systems\mmm_builder\RenderPhp;


class PhpComposite implements PhpCodeInterface {
  /** @var PhpCodeInterface[] */
  protected $parts;

  /**
   * PhpComposite constructor.
   */
  public function __construct() {
  }

  /**
   * @param PhpCodeInterface $part
   * @return $this
   */
  public function addPart(PhpCodeInterface $part) {
    $this->parts[] = $part;
    return $this;
  }

  /**
   * @return string
   */
  public function __toString() {
    return implode('', $this->parts);
  }
}
