<?php


namespace clever_systems\mmm_builder\RenderPhp;


class PhpLines {
  /** @var PhpCodeInterface[] */
  var $lines;

  /**
   * PhpComposite constructor.
   */
  public function __construct() {
  }

  public function addLine(PhpCodeInterface $line) {
    $this->lines[] = $line;
  }

  public function __toString() {
    return implode('"\n"', $this->lines);
  }
}
