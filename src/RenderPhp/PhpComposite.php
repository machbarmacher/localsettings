<?php


namespace clever_systems\mmm_builder\RenderPhp;


class PhpComposite implements PhpCodeInterface {
  /** @var PhpCodeInterface[] */
  var $parts;

  /**
   * PhpComposite constructor.
   */
  public function __construct() {
  }

  public function addPart(PhpCodeInterface $part) {
    $this->parts[] = $part;
  }

  public function __toString() {
    return implode('', $this->parts);
  }
}
