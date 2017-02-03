<?php


namespace clever_systems\mmm_builder\RenderPhp;


class PhpArray implements PhpCodeInterface {
  /** @var mixed[] */
  var $values;
  /** @var int */
  var $indent;

  /**
   * PhpArray constructor.
   * @param int $indent
   */
  public function __construct($indent = 2) {
    $this->indent = $indent;
  }

  /**
   * @param PhpKeyValue|PhpLineComment $line
   */
  public function addLine($line) {
    $values[] = $line;
  }

  public function __toString() {
    $lines = ['['];
    foreach ($this->values as $value) {
      $lines[] = str_repeat(' ', $this->indent) . $value;
    }
    $lines[] = str_repeat(' ', $this->indent) . ']';
    return implode("\n", $lines);
  }

}
